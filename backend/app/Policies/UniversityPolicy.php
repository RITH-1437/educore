<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\University;
use App\Models\User;

class UniversityPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->viewStructure($user);
    }

    public function view(User $user, University $university): bool
    {
        return $this->viewStructure($user);
    }

    public function create(User $user): bool
    {
        return $this->manageStructure($user);
    }

    public function update(User $user, University $university): bool
    {
        return $this->manageStructure($user);
    }

    public function delete(User $user, University $university): bool
    {
        return $this->manageStructure($user);
    }

    /**
     * Anyone who may see the academic structure can list universities.
     */
    private function viewStructure(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value)
            || $user->isRole(Role::UniversityAdmin->value)
            || $user->isRole(Role::DepartmentAdmin->value);
    }

    /**
     * The university record itself is platform-wide data, so only Super Admin
     * and University Admin manage it. A Department Admin manages their own unit,
     * never the university (see `skills/faculty-department/SKILL.md` §8).
     */
    private function manageStructure(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value)
            || $user->isRole(Role::UniversityAdmin->value);
    }
}
