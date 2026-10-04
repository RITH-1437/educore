<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\CourseOffering;
use App\Models\User;

/**
 * Offerings and their sections/lecturer assignments share one policy: managing
 * a section is managing its offering.
 */
class CourseOfferingPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->staff($user);
    }

    /** A Department Admin only sees offerings of their department's courses. */
    public function view(User $user, CourseOffering $offering): bool
    {
        return $this->staff($user) && $offering->isVisibleTo($user);
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, CourseOffering $offering): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, CourseOffering $offering): bool
    {
        return $this->manage($user);
    }

    /**
     * Department Admin reads but does not manage (no unit scope on the user yet).
     */
    private function staff(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value)
            || $user->isRole(Role::UniversityAdmin->value)
            || $user->isRole(Role::DepartmentAdmin->value);
    }

    private function manage(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value)
            || $user->isRole(Role::UniversityAdmin->value);
    }
}
