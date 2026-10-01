<?php

namespace App\Services;

use App\Dto\UniversityStructure\FacultyListFilters;
use App\Exceptions\BusinessRuleException;
use App\Models\Faculty;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Faculty business rules.
 *
 * Controllers authorize through `FacultyPolicy` and then call these methods.
 * The listing query and every write live here so the web and API controllers
 * share one definition.
 *
 * `is_active` is only changed through `archive()` / `reactivate()` so an
 * archived unit cannot be silently reactivated by a crafted payload.
 */
class FacultyService
{
    /**
     * @return LengthAwarePaginator<int, Faculty>
     */
    public function paginate(FacultyListFilters $filters): LengthAwarePaginator
    {
        return Faculty::query()
            ->with('university:id,code,name')
            ->withCount('departments')
            ->withCount([
                'departments as active_departments_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->search($filters->search)
            ->when(
                $filters->universityId,
                fn ($query, $id) => $query->where('university_id', $id),
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
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Faculty
    {
        return DB::transaction(fn () => Faculty::query()->create($attributes)->refresh());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Faculty $faculty, array $attributes): Faculty
    {
        // Archiving and reactivation are deliberate actions, not free-text edits.
        unset($attributes['is_active']);

        return DB::transaction(function () use ($faculty, $attributes) {
            if ($attributes !== []) {
                $faculty->update($attributes);
            }

            return $faculty->refresh();
        });
    }

    /**
     * Archive the faculty instead of deleting it, so programs, courses and
     * lecturers keep a valid parent (`skills/faculty-department/SKILL.md` §4).
     */
    public function archive(Faculty $faculty): Faculty
    {
        return DB::transaction(function () use ($faculty) {
            $faculty->update(['is_active' => false]);

            return $faculty->refresh();
        });
    }

    public function reactivate(Faculty $faculty): Faculty
    {
        return DB::transaction(function () use ($faculty) {
            $faculty->update(['is_active' => true]);

            return $faculty->refresh();
        });
    }

    /**
     * Delete the faculty, refusing while it still has departments
     * (`skills/faculty-department/SKILL.md` §4, §9).
     */
    public function delete(Faculty $faculty): void
    {
        DB::transaction(function () use ($faculty) {
            $departmentCount = $faculty->departments()->count();

            if ($departmentCount > 0) {
                throw new BusinessRuleException(
                    "This faculty still has {$departmentCount} department(s) and cannot be deleted. Archive it instead."
                );
            }

            $faculty->delete();
        });
    }
}
