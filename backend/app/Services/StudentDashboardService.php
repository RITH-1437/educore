<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Enrollment;
use App\Models\Semester;
use App\Models\Student;
use App\Support\AcademicClock;
use Illuminate\Support\Collection;

/**
 * Student academic dashboard (module 9.15): a read-only "at a glance" view.
 *
 * Every number comes from the owning module's service — GPA from
 * `GpaService`, attendance from `AttendanceService`, classes from
 * `TimetableService`, exams from `ExamService`, grades from `GradingService`,
 * announcements from `AnnouncementService`
 * (`skills/analytics-reporting` §12: no duplicated calculations). Only the
 * overall attendance rate is aggregated here, from the per-course counts.
 *
 * "Current semester" = the semester of the student's open (pending /
 * confirmed) enrollments with the latest start date; none when the student has
 * no open enrollment.
 */
class StudentDashboardService
{
    private const LIMIT = 5;

    public function __construct(
        private readonly GpaService $gpa,
        private readonly AttendanceService $attendance,
        private readonly TimetableService $timetable,
        private readonly ExamService $exams,
        private readonly GradingService $grading,
        private readonly AnnouncementService $announcements,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Student $student): array
    {
        $student->loadMissing('currentProgram.program:id,code,name');
        $open = $student->enrollments()
            ->whereIn('status', Enrollment::OPEN_STATUSES)
            ->with('section.offering.course:id,code,name,credits', 'semester.academicYear:id,code')
            ->get();
        $semester = $open->pluck('semester')->filter()->sortBy(fn (Semester $s) => $s->start_date?->toDateString() ?? '')->last();
        $current = $open->where('semester_id', $semester?->id);
        $gpa = $this->gpa->summary($student);

        return [
            'student' => [
                'id' => $student->id,
                'student_number' => $student->student_number,
                'full_name' => $student->fullName(),
                'status' => $student->status,
                'program' => $student->currentProgram?->program ? ['code' => $student->currentProgram->program->code, 'name' => $student->currentProgram->program->name] : null,
            ],
            'semester' => $semester ? [
                'id' => $semester->id,
                'name' => trim(($semester->academicYear?->code ?? '').' '.$semester->name),
                'start_date' => $semester->start_date?->toDateString(),
                'end_date' => $semester->end_date?->toDateString(),
            ] : null,
            'gpa' => [
                'cumulative' => $gpa['cumulative']['gpa'] ?? null,
                'latest_semester' => ($last = end($gpa['semesters'])) ? ['name' => trim($last['academic_year'].' '.$last['semester']), 'gpa' => $last['gpa']] : null,
            ],
            'credits' => [
                'current' => round($current->sum(fn (Enrollment $e) => (float) $e->section->offering->course->credits), 2),
                'courses' => $current->count(),
                'earned' => $gpa['cumulative']['earned_credits'] ?? 0,
                'attempted' => $gpa['cumulative']['attempted_credits'] ?? 0,
            ],
            'attendance' => $this->attendance($student, $current),
            'today' => $this->today($student, $semester),
            'assignments' => $this->assignments($open),
            'exams' => $this->exams->forStudent($student)
                ->filter(fn ($exam) => $exam['scheduled_date'] !== null && $exam['scheduled_date'] >= today()->toDateString())
                ->take(self::LIMIT)->values(),
            'grades' => $this->grading->forStudent($student->id)->reverse()->take(self::LIMIT)->values(),
            'announcements' => $student->user
                ? collect($this->announcements->preloadTargets($this->announcements->feedFor($student->user)->limit(3)->get()))
                    ->map(fn ($a) => ['id' => $a->id, 'title' => $a->title, 'announcement_type' => $a->announcement_type, 'audience' => $this->announcements->audienceLabel($a), 'published_at' => $a->published_at?->toIso8601String()])
                    ->values()
                : collect(),
        ];
    }

    /**
     * Per-course rates of the current semester plus the overall rate.
     *
     * @param  Collection<int, Enrollment>  $current
     * @return array{rate: ?float, courses: list<array<string, mixed>>}
     */
    private function attendance(Student $student, Collection $current): array
    {
        $courses = $this->attendance->studentSummary($student)->whereIn('enrollment_id', $current->modelKeys())->values();
        $attended = $courses->sum(fn ($c) => $c['present'] + $c['late']);
        $counted = $attended + $courses->sum('absent');

        return [
            'rate' => $counted === 0 ? null : round($attended / $counted * 100, 1),
            'courses' => $courses->map(fn ($c) => ['course' => $c['course'], 'section' => $c['section'], 'rate' => $c['rate']])->all(),
        ];
    }

    /**
     * Today's meetings, when today falls inside the current semester.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function today(Student $student, ?Semester $semester): Collection
    {
        // The university's calendar day (report 48), not the UTC one.
        $today = AcademicClock::today();

        if ($semester === null || ! $semester->covers($today)) {
            return collect();
        }

        return $this->timetable->forStudent($student, $semester)
            ->where('day_of_week', $today->dayOfWeekIso)
            ->values();
    }

    /**
     * Published, not yet due and not yet submitted assignments, soonest first.
     *
     * @param  Collection<int, Enrollment>  $open
     * @return Collection<int, array<string, mixed>>
     */
    private function assignments(Collection $open): Collection
    {
        return Assignment::query()
            ->whereIn('section_id', $open->pluck('section_id'))
            ->where('is_published', true)
            ->where('due_at', '>=', now())
            ->whereDoesntHave('submissions', fn ($q) => $q->whereIn('enrollment_id', $open->modelKeys()))
            ->orderBy('due_at')
            ->limit(self::LIMIT)
            ->get()
            ->map(function (Assignment $assignment) use ($open) {
                $section = $open->firstWhere('section_id', $assignment->section_id)->section;

                return [
                    'id' => $assignment->id,
                    'title' => $assignment->title,
                    'due_at' => $assignment->due_at->toIso8601String(),
                    'max_score' => (float) $assignment->max_score,
                    'section_id' => $section->id,
                    'course' => ['code' => $section->offering->course->code, 'name' => $section->offering->course->name],
                ];
            });
    }
}
