<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->viewStructure($user);
    }

    /** A Department Admin only sees their own department. */
    public function view(User $user, Department $department): bool
    {
        return $this->viewStructure($user) && $department->isVisibleTo($user);
    }

    public function create(User $user): bool
    {
        return $this->manageStructure($user);
    }

    public function update(User $user, Department $department): bool
    {
        return $this->manageStructure($user);
    }

    public function archive(User $user, Department $department): bool
    {
        return $this->manageStructure($user);
    }

    public function delete(User $user, Department $department): bool
    {
        return $this->manageStructure($user);
    }

    /**
     * A Department Admin may read the structure but cannot change it — they
     * administer the people and teaching inside their department.
     */
    private function viewStructure(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value)
            || $user->isRole(Role::UniversityAdmin->value)
            || $user->isRole(Role::DepartmentAdmin->value);
    }

    /**
     * @see skills/faculty-department/SKILL.md §8
     */
    private function manageStructure(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value)
            || $user->isRole(Role::UniversityAdmin->value);
    }
}
