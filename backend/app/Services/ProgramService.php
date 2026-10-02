<?php

namespace App\Services;

use App\Dto\UniversityStructure\ProgramListFilters;
use App\Exceptions\BusinessRuleException;
use App\Models\Course;
use App\Models\Program;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Program business rules.
 *
 * Controllers authorize through `ProgramPolicy` and then call these methods.
 *
 * Deleting is restricted while students or curriculum courses still reference
 * the program (`skills/program-management/SKILL.md` §4, §12) — never a silent
 * cascade, even though `course_programs` is `ON DELETE CASCADE` in the schema.
 * The supported alternative is `archive()`.
 */
class ProgramService
{
    /**
     * Tables keyed by a `program_id` column, with a label for the error message.
     *
     * @var array<string, string>
     */
    private const REFERENCING_TABLES = [
        'student_programs' => 'students',
        'course_programs' => 'curriculum courses',
    ];

    /**
     * @return LengthAwarePaginator<int, Program>
     */
    /** `$viewer` limits a Faculty Admin to their faculty (`BelongsToFaculty`). */
    public function paginate(ProgramListFilters $filters, ?User $viewer = null): LengthAwarePaginator
    {
        return Program::query()
            ->when($viewer, fn ($query) => $query->visibleTo($viewer))
            ->with('department.faculty:id,code,name')
            ->search($filters->search)
            ->when($filters->departmentId, fn ($query, $id) => $query->where('department_id', $id))
            ->when(
                $filters->facultyId,
                fn ($query, $id) => $query->whereHas('department', fn ($q) => $q->where('faculty_id', $id)),
            )
            ->when($filters->degreeLevel, fn ($query, $level) => $query->where('degree_level', $level))
            ->when(
                $filters->isActive !== null,
                fn ($query) => $query->where('is_active', $filters->isActive),
            )
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->orderBy('id')
            ->paginate($filters->perPage);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Program
    {
        return DB::transaction(fn () => Program::query()->create($attributes)->refresh());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Program $program, array $attributes): Program
    {
        // State changes go through archive()/reactivate(), not a plain update.
        unset($attributes['is_active']);

        return DB::transaction(function () use ($program, $attributes) {
            if ($attributes !== []) {
                $program->update($attributes);
            }

            return $program->refresh();
        });
    }

    public function archive(Program $program): Program
    {
        return DB::transaction(function () use ($program) {
            $program->update(['is_active' => false]);

            return $program->refresh();
        });
    }

    public function reactivate(Program $program): Program
    {
        return DB::transaction(function () use ($program) {
            $program->update(['is_active' => true]);

            return $program->refresh();
        });
    }

    /**
     * Add a course to the program's curriculum.
     *
     * Duplicates and archived courses are rejected by `StoreProgramCourseRequest`;
     * the `uq_course_programs` constraint is the last line of defence.
     *
     * @param  array{is_required?: bool, suggested_semester?: int|null}  $placement
     */
    public function addCourse(Program $program, Course $course, array $placement = []): Program
    {
        return DB::transaction(function () use ($program, $course, $placement) {
            $program->courses()->attach($course->getKey(), [
                'is_required' => $placement['is_required'] ?? false,
                'suggested_semester' => $placement['suggested_semester'] ?? null,
            ]);

            return $program->refresh();
        });
    }

    /**
     * @param  array{is_required?: bool, suggested_semester?: int|null}  $placement
     */
    public function updateCourse(Program $program, Course $course, array $placement): Program
    {
        return DB::transaction(function () use ($program, $course, $placement) {
            $program->courses()->updateExistingPivot($course->getKey(), array_intersect_key(
                $placement,
                array_flip(['is_required', 'suggested_semester']),
            ));

            return $program->refresh();
        });
    }

    /**
     * Remove a course from the curriculum. Only the link is removed — the
     * course itself, and any grade history that references it, are untouched.
     */
    public function removeCourse(Program $program, Course $course): Program
    {
        return DB::transaction(function () use ($program, $course) {
            $program->courses()->detach($course->getKey());

            return $program->refresh();
        });
    }

    public function isInCurriculum(Program $program, Course $course): bool
    {
        return $program->courses()->whereKey($course->getKey())->exists();
    }

    public function delete(Program $program): void
    {
        DB::transaction(function () use ($program) {
            $referencing = $this->referencing($program);

            if ($referencing !== []) {
                throw new BusinessRuleException(
                    'This program still has '.implode(' and ', $referencing)
                    .' and cannot be deleted. Archive it instead.'
                );
            }

            $program->delete();
        });
    }

    /**
     * Labels of what still references this program.
     *
     * @return list<string>
     */
    private function referencing(Program $program): array
    {
        $labels = [];

        foreach (self::REFERENCING_TABLES as $table => $label) {
            if (DB::table($table)->where('program_id', $program->getKey())->exists()) {
                $labels[] = $label;
            }
        }

        return $labels;
    }
}
