<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Faculty;
use App\Models\User;

class FacultyPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->viewStructure($user);
    }

    public function view(User $user, Faculty $faculty): bool
    {
        return $this->viewStructure($user);
    }

    public function create(User $user): bool
    {
        return $this->manageStructure($user);
    }

    public function update(User $user, Faculty $faculty): bool
    {
        return $this->manageStructure($user);
    }

    public function archive(User $user, Faculty $faculty): bool
    {
        return $this->manageStructure($user);
    }

    public function delete(User $user, Faculty $faculty): bool
    {
        return $this->manageStructure($user);
    }

    /**
     * A Faculty Admin may read the structure but cannot change it — they
     * administer their own unit, which arrives with 9.3 Lecturer Management.
     */
    private function viewStructure(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value)
            || $user->isRole(Role::UniversityAdmin->value)
            || $user->isRole(Role::FacultyAdmin->value);
    }

    /**
     * Creating, editing, archiving and deleting faculties is university-wide
     * data, so only Super Admin and University Admin do it.
     *
     * @see skills/faculty-department/SKILL.md §8
     */
    private function manageStructure(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value)
            || $user->isRole(Role::UniversityAdmin->value);
    }
}
