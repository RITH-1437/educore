<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Unit-owned records (`App\Support\DepartmentScope`). A model defines
 * `scopeInDepartment`; lists call `visibleTo($user)` and policies call
 * `isVisibleTo($user)`, so a Department Admin only ever reaches their
 * department. Everyone else is unaffected (`User::departmentScope()` is null
 * for them).
 */
trait BelongsToDepartment
{
    /**
     * @param  Builder<static>  $query
     */
    abstract public function scopeInDepartment(Builder $query, int $departmentId): void;

    /**
     * @param  Builder<static>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $departmentId = $user->departmentScope();

        if ($departmentId !== null) {
            $query->inDepartment($departmentId);
        }
    }

    public function isVisibleTo(User $user): bool
    {
        $departmentId = $user->departmentScope();

        return $departmentId === null
            || static::query()->withoutGlobalScopes()->whereKey($this->getKey())->inDepartment($departmentId)->exists();
    }
}
