<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Unit-owned records (`App\Support\FacultyScope`). A model defines
 * `scopeInFaculty`; lists call `visibleTo($user)` and policies call
 * `isVisibleTo($user)`, so a Faculty Admin only ever reaches their faculty.
 * Everyone else is unaffected (`User::facultyScope()` is null for them).
 */
trait BelongsToFaculty
{
    /**
     * @param  Builder<static>  $query
     */
    abstract public function scopeInFaculty(Builder $query, int $facultyId): void;

    /**
     * @param  Builder<static>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $facultyId = $user->facultyScope();

        if ($facultyId !== null) {
            $query->inFaculty($facultyId);
        }
    }

    public function isVisibleTo(User $user): bool
    {
        $facultyId = $user->facultyScope();

        return $facultyId === null
            || static::query()->withoutGlobalScopes()->whereKey($this->getKey())->inFaculty($facultyId)->exists();
    }
}
