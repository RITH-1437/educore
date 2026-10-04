<?php

namespace App\Services;

use App\Enums\SemesterStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Lecturer;
use App\Models\Section;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Course offerings, their sections and lecturer assignments (module 9.8).
 *
 * - One offering per course per semester; only active courses, never in a
 *   completed semester (skill course-management §10: no new classes in the past).
 * - Sections: unique code per offering, capacity ≥ 1 and never below the open
 *   enrollments they already hold.
 * - Lecturer assignment: active lecturers only, no duplicates, one primary per
 *   section (skill lecturer-management §9).
 * - Deletes never remove academic history: an offering with sections, or a
 *   section with schedule, enrollments, attendance, assignments or exams, is
 *   refused.
 *
 * Lecturer time clashes are checked through `TimetableService`.
 */
class CourseOfferingService
{
    public function __construct(
        private readonly TimetableService $timetable,
    ) {}

    /** @var array<string, string> */
    private const SECTION_HISTORY = [
        'schedule_entries' => 'schedule entries',
        'enrollments' => 'enrollments',
        'attendance_sessions' => 'attendance sessions',
        'assignments' => 'assignments',
        'exams' => 'exams',
    ];

    /**
     * @param  array{search?: ?string, semester_id?: ?int, academic_year_id?: ?int, course_id?: ?int, status?: ?string, per_page?: int}  $filters
     * @return LengthAwarePaginator<int, CourseOffering>
     */
    /** `$viewer` limits a Department Admin to their department (`BelongsToDepartment`). */
    public function paginate(array $filters, ?User $viewer = null): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return CourseOffering::query()
            ->when($viewer, fn ($query) => $query->visibleTo($viewer))
            ->with(['course:id,code,name,credits,department_id', 'semester:id,name,code,academic_year_id,status', 'semester.academicYear:id,code,name'])
            ->withCount('sections')
            ->withSum('sections', 'capacity')
            ->when($search !== '', fn ($query) => $query->whereHas('course', fn ($q) => $q
                ->where('code', 'ilike', "%{$search}%")->orWhere('name', 'ilike', "%{$search}%")))
            ->when($filters['semester_id'] ?? null, fn ($query, $id) => $query->where('semester_id', $id))
            ->when($filters['academic_year_id'] ?? null, fn ($query, $id) => $query->whereHas('semester', fn ($q) => $q->where('academic_year_id', $id)))
            ->when($filters['course_id'] ?? null, fn ($query, $id) => $query->where('course_id', $id))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('semester_id')
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): CourseOffering
    {
        return DB::transaction(function () use ($data) {
            $course = Course::query()->findOrFail($data['course_id']);
            $semester = Semester::query()->findOrFail($data['semester_id']);

            if ($course->status !== Course::STATUS_ACTIVE) {
                throw ValidationException::withMessages(['course_id' => 'Only an active course can be offered.']);
            }

            $this->assertSemesterNotCompleted($semester);

            return CourseOffering::query()->create($data)->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CourseOffering $offering, array $data): CourseOffering
    {
        return DB::transaction(function () use ($offering, $data) {
            $offering->update($data);

            return $offering->refresh();
        });
    }

    public function delete(CourseOffering $offering): void
    {
        DB::transaction(function () use ($offering) {
            if ($offering->sections()->exists()) {
                throw new BusinessRuleException('This offering has sections and cannot be deleted. Close it instead.');
            }

            $offering->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createSection(CourseOffering $offering, array $data): Section
    {
        return DB::transaction(function () use ($offering, $data) {
            $this->assertSemesterNotCompleted($offering->semester);

            return $offering->sections()->create($data)->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSection(Section $section, array $data): Section
    {
        return DB::transaction(function () use ($section, $data) {
            if (isset($data['capacity'])) {
                $enrolled = $this->openEnrollments($section);

                if ((int) $data['capacity'] < $enrolled) {
                    throw ValidationException::withMessages([
                        'capacity' => "Capacity cannot be below the {$enrolled} students already enrolled.",
                    ]);
                }
            }

            $section->update($data);

            return $section->refresh();
        });
    }

    public function deleteSection(Section $section): void
    {
        DB::transaction(function () use ($section) {
            $history = [];

            foreach (self::SECTION_HISTORY as $table => $label) {
                if (DB::table($table)->where('section_id', $section->getKey())->exists()) {
                    $history[] = $label;
                }
            }

            if ($history !== []) {
                throw new BusinessRuleException(
                    'This section has '.implode(', ', $history).' and cannot be deleted. Archive it instead.'
                );
            }

            // Lecturer assignments cascade with the section (FK ON DELETE CASCADE).
            $section->delete();
        });
    }

    public function assignLecturer(Section $section, Lecturer $lecturer, string $role): Section
    {
        return DB::transaction(function () use ($section, $lecturer, $role) {
            $this->assertSemesterNotCompleted($section->offering->semester);

            if (! $lecturer->is_active) {
                throw ValidationException::withMessages(['lecturer_id' => 'An inactive lecturer cannot be assigned to a section.']);
            }

            if ($section->lecturers()->whereKey($lecturer->getKey())->exists()) {
                throw ValidationException::withMessages(['lecturer_id' => 'This lecturer is already assigned to the section.']);
            }

            $section->loadMissing('scheduleEntries', 'offering');
            $this->timetable->assertLecturerFree($lecturer, $section);

            if ($role === 'primary' && $section->lecturers()->wherePivot('role', 'primary')->exists()) {
                throw ValidationException::withMessages(['role' => 'The section already has a primary lecturer.']);
            }

            $section->lecturers()->attach($lecturer->getKey(), ['role' => $role]);

            return $section->refresh();
        });
    }

    public function removeLecturer(Section $section, Lecturer $lecturer): void
    {
        DB::transaction(fn () => $section->lecturers()->detach($lecturer->getKey()));
    }

    public function openEnrollments(Section $section): int
    {
        return DB::table('enrollments')
            ->where('section_id', $section->getKey())
            ->whereNull('deleted_at')
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();
    }

    private function assertSemesterNotCompleted(Semester $semester): void
    {
        if ($semester->status === SemesterStatus::Completed) {
            throw new BusinessRuleException('The semester is completed; its classes can no longer change.');
        }
    }
}
