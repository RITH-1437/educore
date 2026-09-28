<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Semester;
use App\Models\User;

class SemesterPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->manageCalendar($user);
    }

    public function view(User $user, Semester $semester): bool
    {
        return $this->manageCalendar($user);
    }

    public function create(User $user): bool
    {
        return $this->manageCalendar($user);
    }

    public function update(User $user, Semester $semester): bool
    {
        return $this->manageCalendar($user);
    }

    public function delete(User $user, Semester $semester): bool
    {
        return $this->manageCalendar($user);
    }

    /**
     * Semesters follow the academic calendar: university-wide data.
     */
    private function manageCalendar(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value)
            || $user->isRole(Role::UniversityAdmin->value);
    }
}
