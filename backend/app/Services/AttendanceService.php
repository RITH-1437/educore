<?php

namespace App\Services;

use App\Enums\SemesterStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Enrollment;
use App\Models\ScheduleEntry;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Attendance (module 9.11, `skills/attendance/SKILL.md`).
 *
 * - A session is one dated meeting of a section (unique per date). Recording
 *   is a bulk upsert: only the listed students are touched.
 * - Dates: not in the future, inside the semester dates, never in a completed
 *   semester, and — when the section has a weekly schedule — on one of its
 *   scheduled weekdays (times are copied from that slot).
 * - Only students with a pending/confirmed/completed enrollment in the section
 *   can be marked.
 * - The rate is always derived: (present + late) / (present + late + absent)
 *   over held sessions. Excused and unmarked are excluded; cancelled sessions
 *   are excluded.
 */
class AttendanceService
{
    /** Enrollments that may receive attendance. */
    private const MARKABLE = [Enrollment::STATUS_PENDING, Enrollment::STATUS_CONFIRMED, Enrollment::STATUS_COMPLETED];

    /**
     * @param  list<array{enrollment_id: int, status: string, remarks?: ?string}>  $records
     */
    public function record(Section $section, string $date, array $records, User $by, ?string $topic = null): AttendanceSession
    {
        return DB::transaction(function () use ($section, $date, $records, $by, $topic) {
            $section->loadMissing('offering.semester', 'scheduleEntries');
            $day = Carbon::parse($date)->startOfDay();
            $this->assertDateAllowed($section, $day);

            $markable = $section->enrollments()->whereIn('status', self::MARKABLE)->pluck('id')->all();
            $invalid = array_values(array_diff(array_column($records, 'enrollment_id'), $markable));

            if ($invalid !== []) {
                throw ValidationException::withMessages(['records' => 'Only students enrolled in this section can be marked (invalid: '.implode(', ', $invalid).').']);
            }

            $slot = $section->scheduleEntries->firstWhere('day_of_week', $day->dayOfWeekIso);

            $session = AttendanceSession::query()->firstOrNew(['section_id' => $section->getKey(), 'session_date' => $day->toDateString()]);

            if ($session->exists && $session->status === 'cancelled') {
                throw new BusinessRuleException('This session was cancelled; restore it before recording attendance.');
            }

            $session->fill([
                'start_time' => $session->start_time ?? $slot?->startsAt(),
                'end_time' => $session->end_time ?? $slot?->endsAt(),
                'topic' => $topic ?? $session->topic,
                'status' => 'held',
                'recorded_by' => $by->getKey(),
            ])->save();

            foreach ($records as $row) {
                AttendanceRecord::query()->updateOrCreate(
                    ['attendance_session_id' => $session->getKey(), 'enrollment_id' => $row['enrollment_id']],
                    ['status' => $row['status'], 'remarks' => $row['remarks'] ?? null, 'marked_by' => $by->getKey()],
                );
            }

            return $session->refresh();
        });
    }

    public function cancel(AttendanceSession $session, bool $cancelled = true): AttendanceSession
    {
        return DB::transaction(function () use ($session, $cancelled) {
            if ($session->section->offering->semester->status === SemesterStatus::Completed) {
                throw new BusinessRuleException('The semester is completed; attendance can no longer change.');
            }

            $session->update(['status' => $cancelled ? 'cancelled' : 'held']);

            return $session->refresh();
        });
    }

    /**
     * The roster for one date: every markable enrollment with its status for
     * that session (null when unmarked).
     *
     * @return array{session: ?AttendanceSession, roster: Collection<int, array<string, mixed>>}
     */
    public function roster(Section $section, string $date): array
    {
        $session = AttendanceSession::query()
            ->where('section_id', $section->getKey())
            ->whereDate('session_date', $date)
            ->with('records')
            ->first();

        $statuses = $session?->records->keyBy('enrollment_id') ?? collect();

        $roster = $section->enrollments()
            ->whereIn('status', self::MARKABLE)
            ->with('student:id,student_number,first_name,last_name')
            ->get()
            ->sortBy(fn (Enrollment $e) => $e->student->last_name.' '.$e->student->first_name)
            ->map(fn (Enrollment $e) => [
                'enrollment_id' => $e->id,
                'student' => ['id' => $e->student->id, 'student_number' => $e->student->student_number, 'full_name' => $e->student->fullName()],
                'status' => $statuses->get($e->id)?->status,
                'remarks' => $statuses->get($e->id)?->remarks,
            ])
            ->values();

        return ['session' => $session, 'roster' => $roster];
    }

    /**
     * Per-student counts and rate for a section, aggregated in SQL.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function sectionSummary(Section $section): Collection
    {
        $counts = $this->countsQuery()
            ->where('attendance_sessions.section_id', $section->getKey())
            ->groupBy('attendance_records.enrollment_id')
            ->get()
            ->keyBy('enrollment_id');

        return $section->enrollments()
            ->whereIn('status', self::MARKABLE)
            ->with('student:id,student_number,first_name,last_name')
            ->get()
            ->map(fn (Enrollment $e) => [
                'enrollment_id' => $e->id,
                'student' => ['id' => $e->student->id, 'student_number' => $e->student->student_number, 'full_name' => $e->student->fullName()],
                ...$this->shape($counts->get($e->id)),
            ])
            ->sortBy('student.full_name')
            ->values();
    }

    /**
     * A student's counts and rate per course (current and past enrollments).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function studentSummary(Student $student): Collection
    {
        $enrollments = $student->enrollments()
            ->whereIn('status', self::MARKABLE)
            ->with('section.offering.course:id,code,name', 'semester.academicYear:id,code')
            ->get();

        $counts = $this->countsQuery()
            ->whereIn('attendance_records.enrollment_id', $enrollments->modelKeys())
            ->groupBy('attendance_records.enrollment_id')
            ->get()
            ->keyBy('enrollment_id');

        return $enrollments->map(fn (Enrollment $e) => [
            'enrollment_id' => $e->id,
            'course' => ['code' => $e->section->offering->course->code, 'name' => $e->section->offering->course->name],
            'section' => $e->section->code,
            'semester' => trim(($e->semester->academicYear?->code ?? '').' '.$e->semester->name),
            ...$this->shape($counts->get($e->id)),
        ])->values();
    }

    /**
     * Dates the section is expected to meet in its semester up to today
     * (from its weekly schedule), with whether a session was recorded.
     *
     * @return Collection<int, array{date: string, day: string, recorded: bool, status: ?string}>
     */
    public function expectedDates(Section $section): Collection
    {
        $section->loadMissing('offering.semester', 'scheduleEntries');
        $semester = $section->offering->semester;
        $days = $section->scheduleEntries->pluck('day_of_week')->unique()->all();

        if ($days === [] || $semester->start_date === null) {
            return collect();
        }

        $sessions = AttendanceSession::query()->where('section_id', $section->getKey())->get()->keyBy(fn ($s) => $s->session_date->toDateString());
        $end = Carbon::now()->startOfDay()->min($semester->end_date ?? Carbon::now());
        $dates = collect();

        for ($day = $semester->start_date->copy(); $day->lte($end); $day->addDay()) {
            if (in_array($day->dayOfWeekIso, $days, true)) {
                $session = $sessions->get($day->toDateString());
                $dates->push(['date' => $day->toDateString(), 'day' => ScheduleEntry::DAYS[$day->dayOfWeekIso], 'recorded' => $session !== null, 'status' => $session?->status]);
            }
        }

        return $dates->reverse()->values();
    }

    private function countsQuery(): Builder
    {
        return DB::table('attendance_records')
            ->join('attendance_sessions', 'attendance_sessions.id', '=', 'attendance_records.attendance_session_id')
            ->where('attendance_sessions.status', 'held')
            ->select('attendance_records.enrollment_id')
            ->selectRaw("count(*) filter (where attendance_records.status = 'present') as present")
            ->selectRaw("count(*) filter (where attendance_records.status = 'late') as late")
            ->selectRaw("count(*) filter (where attendance_records.status = 'absent') as absent")
            ->selectRaw("count(*) filter (where attendance_records.status = 'excused') as excused");
    }

    /**
     * @return array{present: int, late: int, absent: int, excused: int, rate: ?float}
     */
    private function shape(?object $row): array
    {
        $present = (int) ($row->present ?? 0);
        $late = (int) ($row->late ?? 0);
        $absent = (int) ($row->absent ?? 0);
        $counted = $present + $late + $absent;

        return [
            'present' => $present,
            'late' => $late,
            'absent' => $absent,
            'excused' => (int) ($row->excused ?? 0),
            'rate' => $counted === 0 ? null : round(($present + $late) / $counted * 100, 1),
        ];
    }

    private function assertDateAllowed(Section $section, Carbon $day): void
    {
        $semester = $section->offering->semester;

        if ($semester->status === SemesterStatus::Completed) {
            throw new BusinessRuleException('The semester is completed; attendance can no longer change.');
        }

        if ($day->gt(Carbon::now()->startOfDay())) {
            throw ValidationException::withMessages(['session_date' => 'Attendance cannot be recorded for a future date.']);
        }

        if (($semester->start_date && $day->lt($semester->start_date)) || ($semester->end_date && $day->gt($semester->end_date))) {
            throw ValidationException::withMessages(['session_date' => 'The date is outside the semester.']);
        }

        $days = $section->scheduleEntries->pluck('day_of_week')->unique();

        if ($days->isNotEmpty() && ! $days->contains($day->dayOfWeekIso)) {
            throw ValidationException::withMessages(['session_date' => 'This section does not meet on '.ScheduleEntry::DAYS[$day->dayOfWeekIso].'.']);
        }
    }
}
