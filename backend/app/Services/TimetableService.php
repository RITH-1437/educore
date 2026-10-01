<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Enrollment;
use App\Models\Lecturer;
use App\Models\Room;
use App\Models\ScheduleEntry;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Rooms, weekly schedule entries and every conflict check
 * (`skills/timetable/SKILL.md` §4, §6). All checks are scoped to the
 * section's semester and reject **partial** overlaps
 * (a.start < b.end AND a.end > b.start), not just equal slots.
 *
 * Also called by enrollment (student clash) and lecturer assignment (lecturer
 * clash) so no path can double-book anyone.
 */
class TimetableService
{
    /** Teaching day bounds (institution convention). */
    public const DAY_START = '06:00';

    public const DAY_END = '22:00';

    // ------------------------------------------------------------------ rooms

    /**
     * @param  array<string, mixed>  $data
     */
    public function createRoom(array $data): Room
    {
        return DB::transaction(fn () => Room::query()->create($data)->refresh());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateRoom(Room $room, array $data): Room
    {
        return DB::transaction(function () use ($room, $data) {
            $room->update($data);

            return $room->refresh();
        });
    }

    public function deleteRoom(Room $room): void
    {
        DB::transaction(function () use ($room) {
            if ($room->scheduleEntries()->exists()) {
                throw new BusinessRuleException('This room has scheduled classes and cannot be deleted. Deactivate it instead.');
            }

            $room->delete();
        });
    }

    // -------------------------------------------------------- schedule entries

    /**
     * @param  array{room_id: int, day_of_week: int, start_time: string, end_time: string}  $data
     */
    public function addEntry(Section $section, array $data): ScheduleEntry
    {
        return DB::transaction(function () use ($section, $data) {
            $this->validateSlot($section, $data);

            return $section->scheduleEntries()->create($data)->refresh();
        });
    }

    /**
     * @param  array{room_id: int, day_of_week: int, start_time: string, end_time: string}  $data
     */
    public function updateEntry(ScheduleEntry $entry, array $data): ScheduleEntry
    {
        return DB::transaction(function () use ($entry, $data) {
            $this->validateSlot($entry->section, $data, $entry->getKey());
            $entry->update($data);

            return $entry->refresh();
        });
    }

    public function removeEntry(ScheduleEntry $entry): void
    {
        DB::transaction(fn () => $entry->delete());
    }

    /**
     * Refuse a lecturer assignment that would put them in two places at once.
     */
    public function assertLecturerFree(Lecturer $lecturer, Section $section): void
    {
        $semesterId = $section->offering->semester_id;

        foreach ($section->scheduleEntries as $entry) {
            $clash = $this->overlapping($semesterId, $entry->day_of_week, $entry->startsAt(), $entry->endsAt())
                ->whereIn('schedule_entries.section_id', $lecturer->sections()->pluck('sections.id'))
                ->where('schedule_entries.section_id', '!=', $section->getKey())
                ->first();

            if ($clash !== null) {
                throw ValidationException::withMessages([
                    'lecturer_id' => "{$lecturer->fullName()} already teaches {$clash->course_code} {$clash->section_code} at that time.",
                ]);
            }
        }
    }

    /**
     * Refuse an enrollment whose meetings overlap the student's other open
     * enrollments in the same semester.
     */
    public function assertStudentFree(Student $student, Section $section): void
    {
        $semesterId = $section->offering->semester_id;
        $otherSections = Enrollment::query()
            ->where('student_id', $student->getKey())
            ->where('semester_id', $semesterId)
            ->whereIn('status', Enrollment::OPEN_STATUSES)
            ->where('section_id', '!=', $section->getKey())
            ->pluck('section_id');

        if ($otherSections->isEmpty()) {
            return;
        }

        foreach ($section->scheduleEntries as $entry) {
            $clash = $this->overlapping($semesterId, $entry->day_of_week, $entry->startsAt(), $entry->endsAt())
                ->whereIn('schedule_entries.section_id', $otherSections)
                ->first();

            if ($clash !== null) {
                throw ValidationException::withMessages([
                    'section_id' => "Timetable clash with {$clash->course_code} {$clash->section_code} on ".ScheduleEntry::DAYS[$entry->day_of_week].'.',
                ]);
            }
        }
    }

    /**
     * Weekly grid for a student: meetings of their open enrollments.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function forStudent(Student $student, ?Semester $semester = null): Collection
    {
        $sectionIds = Enrollment::query()
            ->where('student_id', $student->getKey())
            ->whereIn('status', [...Enrollment::OPEN_STATUSES, Enrollment::STATUS_COMPLETED])
            ->when($semester, fn ($q) => $q->where('semester_id', $semester->getKey()))
            ->pluck('section_id');

        return $this->grid($sectionIds);
    }

    /**
     * Weekly grid for a lecturer: meetings of their assigned sections.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function forLecturer(Lecturer $lecturer, ?Semester $semester = null): Collection
    {
        $sectionIds = $lecturer->sections()
            ->when($semester, fn ($q) => $q->whereHas('offering', fn ($o) => $o->where('semester_id', $semester->getKey())))
            ->pluck('sections.id');

        return $this->grid($sectionIds);
    }

    /**
     * @param  Collection<int, int>  $sectionIds
     * @return Collection<int, array<string, mixed>>
     */
    private function grid(Collection $sectionIds): Collection
    {
        return ScheduleEntry::query()
            ->with(['room:id,code,name,building', 'section.offering.course:id,code,name', 'section.offering.semester:id,name,status', 'section.lecturers'])
            ->whereIn('section_id', $sectionIds)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->map(fn (ScheduleEntry $entry) => [
                'id' => $entry->id,
                'day_of_week' => $entry->day_of_week,
                'day' => ScheduleEntry::DAYS[$entry->day_of_week],
                'start_time' => $entry->startsAt(),
                'end_time' => $entry->endsAt(),
                'room' => ['code' => $entry->room->code, 'name' => $entry->room->name, 'building' => $entry->room->building],
                'section' => ['id' => $entry->section->id, 'code' => $entry->section->code],
                'course' => ['code' => $entry->section->offering->course->code, 'name' => $entry->section->offering->course->name],
                'semester' => $entry->section->offering->semester->name,
                'lecturer' => $entry->section->lecturers->firstWhere('pivot.role', 'primary')?->fullName(),
            ])
            ->values();
    }

    /**
     * @param  array{room_id: int, day_of_week: int, start_time: string, end_time: string}  $data
     */
    private function validateSlot(Section $section, array $data, ?int $ignoreId = null): void
    {
        $section->loadMissing('offering.semester', 'lecturers');
        $semesterId = $section->offering->semester_id;
        $day = (int) $data['day_of_week'];
        $start = substr($data['start_time'], 0, 5);
        $end = substr($data['end_time'], 0, 5);

        if ($start >= $end) {
            throw ValidationException::withMessages(['end_time' => 'The end time must be after the start time.']);
        }

        if ($start < self::DAY_START || $end > self::DAY_END) {
            throw ValidationException::withMessages(['start_time' => 'Classes must be between '.self::DAY_START.' and '.self::DAY_END.'.']);
        }

        if ($section->offering->semester->status->value === 'completed') {
            throw new BusinessRuleException('The semester is completed; its timetable can no longer change.');
        }

        // Lock the room row: serializes concurrent bookings of the same room
        // now that room slots are checked per semester, not by a DB constraint.
        $room = Room::query()->whereKey($data['room_id'])->lockForUpdate()->firstOrFail();

        if (! $room->is_active) {
            throw ValidationException::withMessages(['room_id' => 'This room is inactive.']);
        }

        $enrolled = Enrollment::query()->where('section_id', $section->getKey())->whereIn('status', Enrollment::OPEN_STATUSES)->count();
        if ($room->capacity < $enrolled) {
            throw ValidationException::withMessages(['room_id' => "Room {$room->code} seats {$room->capacity}, but {$enrolled} students are enrolled."]);
        }

        $slot = fn () => $this->overlapping($semesterId, $day, $start, $end)
            ->when($ignoreId, fn ($q) => $q->where('schedule_entries.id', '!=', $ignoreId));

        if ($clash = $slot()->where('schedule_entries.section_id', $section->getKey())->first()) {
            throw ValidationException::withMessages(['start_time' => "This section already meets {$clash->start_time}–{$clash->end_time} that day."]);
        }

        if ($clash = $slot()->where('schedule_entries.room_id', $room->getKey())->first()) {
            throw ValidationException::withMessages(['room_id' => "Room {$room->code} is booked by {$clash->course_code} {$clash->section_code} at that time."]);
        }

        $lecturerSections = DB::table('section_lecturers')
            ->whereIn('lecturer_id', $section->lecturers->modelKeys())
            ->where('section_id', '!=', $section->getKey())
            ->pluck('section_id');

        if ($lecturerSections->isNotEmpty() && ($clash = $slot()->whereIn('schedule_entries.section_id', $lecturerSections)->first())) {
            throw ValidationException::withMessages(['start_time' => "A lecturer of this section teaches {$clash->course_code} {$clash->section_code} at that time."]);
        }

        $students = Enrollment::query()->where('section_id', $section->getKey())->whereIn('status', Enrollment::OPEN_STATUSES)->pluck('student_id');
        if ($students->isNotEmpty()) {
            $studentSections = Enrollment::query()
                ->whereIn('student_id', $students)
                ->where('semester_id', $semesterId)
                ->whereIn('status', Enrollment::OPEN_STATUSES)
                ->where('section_id', '!=', $section->getKey())
                ->pluck('section_id');

            if ($studentSections->isNotEmpty() && ($clash = $slot()->whereIn('schedule_entries.section_id', $studentSections)->first())) {
                throw ValidationException::withMessages(['start_time' => "Enrolled students also take {$clash->course_code} {$clash->section_code} at that time."]);
            }
        }
    }

    /**
     * Entries in the same semester and day whose time range overlaps [start, end).
     */
    private function overlapping(int $semesterId, int $day, string $start, string $end): Builder
    {
        return DB::table('schedule_entries')
            ->join('sections', 'sections.id', '=', 'schedule_entries.section_id')
            ->join('course_offerings', 'course_offerings.id', '=', 'sections.course_offering_id')
            ->join('courses', 'courses.id', '=', 'course_offerings.course_id')
            ->where('course_offerings.semester_id', $semesterId)
            ->where('schedule_entries.day_of_week', $day)
            ->where('schedule_entries.start_time', '<', $end)
            ->where('schedule_entries.end_time', '>', $start)
            ->select('schedule_entries.*', 'courses.code as course_code', 'sections.code as section_code');
    }
}
