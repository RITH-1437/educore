<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Lecturer;
use App\Models\User;

class LecturerPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->staff($user);
    }

    /**
     * Staff may view any lecturer; a lecturer may view only their own profile
     * (`skills/lecturer-management/SKILL.md` §8, §11 scoping).
     */
    public function view(User $user, Lecturer $lecturer): bool
    {
        return $this->staff($user)
            || ($user->isRole(Role::Lecturer->value) && $lecturer->user_id === $user->getKey());
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, Lecturer $lecturer): bool
    {
        return $this->manage($user);
    }

    public function deactivate(User $user, Lecturer $lecturer): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, Lecturer $lecturer): bool
    {
        return $this->manage($user);
    }

    /**
     * Faculty Admin reads but does not manage: the user row has no
     * faculty/department scope yet, so "manage in scope" cannot be enforced.
     */
    private function staff(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value)
            || $user->isRole(Role::UniversityAdmin->value)
            || $user->isRole(Role::FacultyAdmin->value);
    }

    private function manage(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value)
            || $user->isRole(Role::UniversityAdmin->value);
    }
}
