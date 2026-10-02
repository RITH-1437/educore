<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Program;
use App\Models\User;

class ProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->viewStructure($user);
    }

    /** A Faculty Admin only sees their own faculty's records. */
    public function view(User $user, Program $program): bool
    {
        return $this->viewStructure($user) && $program->isVisibleTo($user);
    }

    public function create(User $user): bool
    {
        return $this->manageStructure($user);
    }

    public function update(User $user, Program $program): bool
    {
        return $this->manageStructure($user);
    }

    public function archive(User $user, Program $program): bool
    {
        return $this->manageStructure($user);
    }

    public function delete(User $user, Program $program): bool
    {
        return $this->manageStructure($user);
    }

    /**
     * A Faculty Admin may read programs but not change them: the user row has
     * no faculty/department scope yet, so "own unit only" cannot be enforced
     * (same limitation documented for `FacultyPolicy`).
     */
    private function viewStructure(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value)
            || $user->isRole(Role::UniversityAdmin->value)
            || $user->isRole(Role::FacultyAdmin->value);
    }

    /**
     * @see skills/program-management/SKILL.md §8
     */
    private function manageStructure(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value)
            || $user->isRole(Role::UniversityAdmin->value);
    }
}
