<?php

namespace App\Services;

use App\Dto\UniversityStructure\CourseListFilters;
use App\Exceptions\BusinessRuleException;
use App\Models\Course;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Course business rules.
 *
 * Controllers authorize through `CoursePolicy` and then call these methods.
 *
 * Courses are never hard-deleted while anything references them
 * (`skills/course-management/SKILL.md` §4, §12): the supported alternative is
 * `archive()`. Prerequisites must exist, may not point at the course itself and
 * must stay acyclic.
 *
 * Program ↔ course membership is *not* handled here — it lives with
 * `ProgramService` (skill §12: no duplicated membership logic).
 */
class CourseService
{
    public function __construct(
        private readonly GpaService $gpa,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Course>
     */
    /** `$viewer` limits a Department Admin to their department (`BelongsToDepartment`). */
    public function paginate(CourseListFilters $filters, ?User $viewer = null): LengthAwarePaginator
    {
        return Course::query()
            ->when($viewer, fn ($query) => $query->visibleTo($viewer))
            ->with('department:id,code,name')
            ->withCount(['prerequisites', 'programs'])
            ->search($filters->search)
            ->when($filters->departmentId, fn ($query, $id) => $query->where('department_id', $id))
            ->when(
                $filters->programId,
                fn ($query, $id) => $query->whereHas('programs', fn ($q) => $q->where('programs.id', $id)),
            )
            ->when($filters->status, fn ($query, $status) => $query->where('status', $status))
            ->when($filters->level, fn ($query, $level) => $query->where('course_level', $level))
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->orderBy('id')
            ->paginate($filters->perPage);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Course
    {
        return DB::transaction(fn () => Course::query()->create($attributes)->refresh());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Course $course, array $attributes): Course
    {
        // An archived course is brought back with reactivate(), never by a
        // plain edit, so the lifecycle has one entry point per direction.
        if ($course->isArchived()) {
            unset($attributes['status']);
        }

        return DB::transaction(function () use ($course, $attributes) {
            if ($attributes !== []) {
                $course->update($attributes);
            }

            // GPA is credit-weighted, so a credit change recomputes it
            // (`skills/grading-gpa/SKILL.md` §4 — never stale).
            if ($course->wasChanged('credits')) {
                $this->gpa->recalculateForCourse($course);
            }

            return $course->refresh();
        });
    }

    public function archive(Course $course): Course
    {
        return DB::transaction(function () use ($course) {
            $course->update(['status' => Course::STATUS_ARCHIVED]);

            return $course->refresh();
        });
    }

    public function reactivate(Course $course): Course
    {
        return DB::transaction(function () use ($course) {
            $course->update(['status' => Course::STATUS_ACTIVE]);

            return $course->refresh();
        });
    }

    public function delete(Course $course): void
    {
        DB::transaction(function () use ($course) {
            $referencing = $this->referencing($course);

            if ($referencing !== []) {
                throw new BusinessRuleException(
                    'This course is still used by '.implode(', ', $referencing)
                    .' and cannot be deleted. Archive it instead.'
                );
            }

            $course->delete();
        });
    }

    /**
     * Add a prerequisite to a course.
     *
     * @throws ValidationException when the link would be a duplicate, point at
     *                             an archived course, or create a cycle
     */
    public function addPrerequisite(Course $course, Course $prerequisite, bool $isStrict = true): Course
    {
        return DB::transaction(function () use ($course, $prerequisite, $isStrict) {
            if ($course->is($prerequisite)) {
                $this->reject('A course cannot be its own prerequisite.');
            }

            if ($prerequisite->isArchived()) {
                $this->reject('An archived course cannot be used as a prerequisite.');
            }

            if ($course->prerequisites()->whereKey($prerequisite->getKey())->exists()) {
                $this->reject('This prerequisite is already set for the course.');
            }

            if ($this->reaches($prerequisite, $course)) {
                $this->reject(
                    "{$prerequisite->code} already depends on {$course->code}; adding it would create a prerequisite cycle."
                );
            }

            $course->prerequisites()->attach($prerequisite->getKey(), ['is_strict' => $isStrict]);

            return $course->refresh();
        });
    }

    public function removePrerequisite(Course $course, Course $prerequisite): void
    {
        DB::transaction(function () use ($course, $prerequisite) {
            $course->prerequisites()->detach($prerequisite->getKey());
        });
    }

    /**
     * Whether `$from` depends — directly or through a chain — on `$target`.
     * Walks the prerequisite graph iteratively, so cycles in bad legacy data
     * cannot loop forever.
     */
    private function reaches(Course $from, Course $target): bool
    {
        $seen = [];
        $queue = [$from->getKey()];

        while ($queue !== []) {
            $current = array_pop($queue);

            if ($current === $target->getKey()) {
                return true;
            }

            if (isset($seen[$current])) {
                continue;
            }
            $seen[$current] = true;

            $next = DB::table('course_prerequisites')
                ->where('course_id', $current)
                ->pluck('prerequisite_course_id');

            foreach ($next as $id) {
                $queue[] = (int) $id;
            }
        }

        return false;
    }

    /**
     * What still references this course.
     *
     * @return list<string>
     */
    private function referencing(Course $course): array
    {
        $labels = [];

        if (DB::table('course_programs')->where('course_id', $course->getKey())->exists()) {
            $labels[] = 'a program curriculum';
        }

        if (DB::table('course_prerequisites')->where('prerequisite_course_id', $course->getKey())->exists()) {
            $labels[] = 'another course as a prerequisite';
        }

        if (DB::table('course_offerings')->where('course_id', $course->getKey())->exists()) {
            $labels[] = 'course offerings';
        }

        return $labels;
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['prerequisite_course_id' => $message]);
    }
}
