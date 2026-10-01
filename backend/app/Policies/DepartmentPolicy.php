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

    public function view(User $user, Department $department): bool
    {
        return $this->viewStructure($user);
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
     * A Faculty Admin may read the structure but cannot change it — they
     * administer their own department, which arrives with 9.3 Lecturer
     * Management.
     */
    private function viewStructure(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value)
            || $user->isRole(Role::UniversityAdmin->value)
            || $user->isRole(Role::FacultyAdmin->value);
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
