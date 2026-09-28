<?php

namespace App\Services;

use App\Dto\AcademicYear\AcademicYearListFilters;
use App\Enums\AcademicYearStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\AcademicYear;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Academic year business rules.
 *
 * Controllers authorize through `AcademicYearPolicy` and then call these
 * methods; the listing query and every write live here so the web and API
 * controllers share one definition.
 *
 * `status` and `is_current` are deliberately not plain mass-assignable fields:
 * they only ever change through `applyStatus()` / `makeCurrent()` so the
 * transition rules cannot be bypassed by a crafted payload.
 */
class AcademicYearService
{
    /**
     * @return LengthAwarePaginator<int, AcademicYear>
     */
    public function paginate(AcademicYearListFilters $filters): LengthAwarePaginator
    {
        return AcademicYear::query()
            ->withCount('semesters')
            ->when($filters->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'ilike', "%{$search}%")
                        ->orWhere('name', 'ilike', "%{$search}%");
                });
            })
            ->when($filters->status, fn ($query, $status) => $query->where('status', $status))
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->paginate($filters->perPage);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): AcademicYear
    {
        return DB::transaction(function () use ($attributes) {
            $makeCurrent = (bool) ($attributes['is_current'] ?? false);
            unset($attributes['is_current']);

            $academicYear = AcademicYear::query()->create($attributes);

            if ($makeCurrent) {
                $this->makeCurrent($academicYear);
            }

            return $academicYear->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(AcademicYear $academicYear, array $attributes): AcademicYear
    {
        $status = isset($attributes['status'])
            ? AcademicYearStatus::from((string) $attributes['status'])
            : null;
        $makeCurrent = ! empty($attributes['is_current']);
        unset($attributes['status'], $attributes['is_current']);

        return DB::transaction(function () use ($academicYear, $attributes, $status, $makeCurrent) {
            if ($status !== null) {
                $this->applyStatus($academicYear, $status);
            }

            if ($attributes !== []) {
                $academicYear->update($attributes);
            }

            if ($makeCurrent && ! $academicYear->fresh()->is_current) {
                $this->makeCurrent($academicYear);
            }

            return $academicYear->refresh();
        });
    }

    /**
     * Move an academic year along planned -> active -> completed.
     */
    public function changeStatus(AcademicYear $academicYear, AcademicYearStatus $status): AcademicYear
    {
        return DB::transaction(fn () => $this->applyStatus($academicYear, $status));
    }

    /**
     * Flag the year as the current one, clearing the previous flag.
     */
    public function makeCurrent(AcademicYear $academicYear): AcademicYear
    {
        return DB::transaction(function () use ($academicYear) {
            if ($academicYear->status !== AcademicYearStatus::Active) {
                throw new BusinessRuleException('Activate the academic year before making it the current one.');
            }

            $this->clearCurrentFlag($academicYear->getKey());

            $academicYear->update(['is_current' => true]);

            return $academicYear->refresh();
        });
    }

    public function delete(AcademicYear $academicYear): void
    {
        DB::transaction(function () use ($academicYear) {
            if ($academicYear->is_current) {
                throw new BusinessRuleException('The current academic year cannot be deleted.');
            }

            $semesterCount = $academicYear->semesters()->count();

            if ($semesterCount > 0) {
                throw new BusinessRuleException(
                    "This academic year still has {$semesterCount} semester(s) and cannot be deleted."
                );
            }

            $academicYear->delete();
        });
    }

    /**
     * Apply a status transition, rejecting any move the domain forbids.
     */
    private function applyStatus(AcademicYear $academicYear, AcademicYearStatus $status): AcademicYear
    {
        $current = $academicYear->status;

        if ($current !== $status && ! $current->canTransitionTo($status)) {
            throw new BusinessRuleException(
                "An academic year cannot move from {$current->label()} to {$status->label()}."
            );
        }

        $attributes = ['status' => $status];

        // A completed year can no longer be the live one.
        if ($status === AcademicYearStatus::Completed && $academicYear->is_current) {
            $attributes['is_current'] = false;
        }

        $academicYear->update($attributes);

        return $academicYear;
    }

    /**
     * Clear `is_current` everywhere except the given year, so at most one
     * academic year is ever current.
     */
    private function clearCurrentFlag(?int $exceptId = null): void
    {
        AcademicYear::query()
            ->where('is_current', true)
            ->when($exceptId, fn ($query, $id) => $query->whereKeyNot($id))
            ->update(['is_current' => false]);
    }
}
