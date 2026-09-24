<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->manageUsers($user);
    }

    public function view(User $user, User $subject): bool
    {
        return $this->manageUsers($user);
    }

    public function create(User $user): bool
    {
        return $this->manageUsers($user);
    }

    public function update(User $user, User $subject): bool
    {
        return $this->manageUsers($user);
    }

    public function delete(User $user, User $subject): bool
    {
        if (! $this->manageUsers($user)) {
            return false;
        }

        return $user->getKey() !== $subject->getKey();
    }

    private function manageUsers(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value);
    }
}
