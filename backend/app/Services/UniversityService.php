<?php

namespace App\Services;

use App\Dto\UniversityStructure\UniversityListFilters;
use App\Exceptions\BusinessRuleException;
use App\Models\University;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * University business rules.
 *
 * Controllers authorize through `UniversityPolicy` and then call these methods,
 * so the web and API controllers share one definition.
 *
 * `is_current` is deliberately not a plain mass-assignable field: at most one
 * university may hold it, so it only ever changes through `makeCurrent()`, which
 * a crafted payload cannot reach directly.
 */
class UniversityService
{
    /**
     * @return LengthAwarePaginator<int, University>
     */
    public function paginate(UniversityListFilters $filters): LengthAwarePaginator
    {
        return University::query()
            ->withCount('departments')
            ->when($filters->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'ilike', "%{$search}%")
                        ->orWhere('name', 'ilike', "%{$search}%")
                        ->orWhere('short_name', 'ilike', "%{$search}%");
                });
            })
            ->when(
                $filters->isCurrent !== null,
                fn ($query) => $query->where('is_current', $filters->isCurrent),
            )
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->orderBy('id')
            ->paginate($filters->perPage);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): University
    {
        return DB::transaction(function () use ($attributes) {
            $makeCurrent = (bool) ($attributes['is_current'] ?? false);
            unset($attributes['is_current']);

            $university = University::query()->create($attributes);

            if ($makeCurrent) {
                $this->makeCurrent($university);
            }

            return $university->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(University $university, array $attributes): University
    {
        $makeCurrent = ! empty($attributes['is_current']);
        unset($attributes['is_current']);

        return DB::transaction(function () use ($university, $attributes, $makeCurrent) {
            if ($attributes !== []) {
                $university->update($attributes);
            }

            if ($makeCurrent && ! $university->fresh()->is_current) {
                $this->makeCurrent($university);
            }

            return $university->refresh();
        });
    }

    /**
     * Flag the university as the current one, clearing the previous flag.
     */
    public function makeCurrent(University $university): University
    {
        return DB::transaction(function () use ($university) {
            $this->clearCurrentFlag($university->getKey());

            $university->update(['is_current' => true]);

            return $university->refresh();
        });
    }

    public function delete(University $university): void
    {
        DB::transaction(function () use ($university) {
            if ($university->is_current) {
                throw new BusinessRuleException('The current university cannot be deleted.');
            }

            // `withTrashed()` matters: a soft-deleted department still points at
            // this university, so a hard delete would fail on the foreign key
            // instead of returning a clean business-rule message.
            $departmentCount = $university->departments()->withTrashed()->count();

            if ($departmentCount > 0) {
                throw new BusinessRuleException(
                    "This university still has {$departmentCount} department(s) and cannot be deleted."
                );
            }

            $university->delete();
        });
    }

    /**
     * Clear `is_current` everywhere except the given university, so at most one
     * university is ever current.
     */
    private function clearCurrentFlag(?int $exceptId = null): void
    {
        University::query()
            ->where('is_current', true)
            ->when($exceptId, fn ($query, $id) => $query->whereKeyNot($id))
            ->update(['is_current' => false]);
    }
}
