<?php

namespace App\Services;

use App\Dto\UniversityStructure\DepartmentListFilters;
use App\Exceptions\BusinessRuleException;
use App\Models\Department;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Department business rules.
 *
 * Controllers authorize through `DepartmentPolicy` and then call these methods.
 *
 * Deleting is restricted whenever anything still points at the department
 * (`skills/faculty-department/SKILL.md` §4, §9) — never a silent cascade. The
 * supported alternative is `archive()`, which keeps referential integrity for
 * programs, courses and lecturers.
 *
 * `programs`, `courses` and `lecturers` are counted with the query builder
 * because those models do not exist yet; the FK columns are what matters and it
 * keeps this module independent of unbuilt ones.
 */
class DepartmentService
{
    /**
     * Child tables keyed by a `department_id` column.
     *
     * @var list<string>
     */
    private const CHILD_TABLES = ['programs', 'courses', 'lecturers'];

    /**
     * @return LengthAwarePaginator<int, Department>
     */
    /** `$viewer` limits a Faculty Admin to their faculty (`BelongsToFaculty`). */
    public function paginate(DepartmentListFilters $filters, ?User $viewer = null): LengthAwarePaginator
    {
        return Department::query()
            ->when($viewer, fn ($query) => $query->visibleTo($viewer))
            ->with('faculty:id,code,name,university_id')
            ->search($filters->search)
            ->when(
                $filters->facultyId,
                fn ($query, $id) => $query->where('faculty_id', $id),
            )
            ->when(
                $filters->isActive !== null,
                fn ($query) => $query->where('is_active', $filters->isActive),
            )
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->orderBy('id')
            ->paginate($filters->perPage);
    }

    /**
     * Departments for exactly the given faculties, for the faculty → department
     * tree rendered on the faculty list screen.
     *
     * Scoping to the visible faculties avoids paginating the whole university
     * structure into a page that only shows one slice of it.
     *
     * @param  list<int|string>  $facultyIds
     * @return Collection<int, Department>
     */
    public function listForFaculties(array $facultyIds): Collection
    {
        if ($facultyIds === []) {
            return collect();
        }

        return Department::query()
            ->whereIn('faculty_id', $facultyIds)
            ->orderBy('faculty_id')
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Department
    {
        return DB::transaction(fn () => Department::query()->create($attributes)->refresh());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Department $department, array $attributes): Department
    {
        // Moving a department to another faculty is allowed; its children keep
        // pointing at the same department id, so their references stay valid
        // (`skills/faculty-department/SKILL.md` §10).
        unset($attributes['is_active']);

        return DB::transaction(function () use ($department, $attributes) {
            if ($attributes !== []) {
                $department->update($attributes);
            }

            return $department->refresh();
        });
    }

    public function archive(Department $department): Department
    {
        return DB::transaction(function () use ($department) {
            $department->update(['is_active' => false]);

            return $department->refresh();
        });
    }

    public function reactivate(Department $department): Department
    {
        return DB::transaction(function () use ($department) {
            $department->update(['is_active' => true]);

            return $department->refresh();
        });
    }

    public function delete(Department $department): void
    {
        DB::transaction(function () use ($department) {
            $referencing = $this->referencingTables($department);

            if ($referencing !== []) {
                throw new BusinessRuleException(
                    'This department is still referenced by '.implode(', ', $referencing)
                    .' and cannot be deleted. Archive it instead.'
                );
            }

            $department->delete();
        });
    }

    /**
     * Child tables that still reference this department.
     *
     * @return list<string>
     */
    private function referencingTables(Department $department): array
    {
        return array_values(array_filter(
            self::CHILD_TABLES,
            fn (string $table) => DB::table($table)->where('department_id', $department->getKey())->exists(),
        ));
    }
}
