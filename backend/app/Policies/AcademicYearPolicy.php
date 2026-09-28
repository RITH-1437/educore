<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\User;

class AcademicYearPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->manageCalendar($user);
    }

    public function view(User $user, AcademicYear $academicYear): bool
    {
        return $this->manageCalendar($user);
    }

    public function create(User $user): bool
    {
        return $this->manageCalendar($user);
    }

    public function update(User $user, AcademicYear $academicYear): bool
    {
        return $this->manageCalendar($user);
    }

    public function delete(User $user, AcademicYear $academicYear): bool
    {
        return $this->manageCalendar($user);
    }

    /**
     * The academic calendar is university-wide data (see
     * `skills/authorization/SKILL.md`).
     */
    private function manageCalendar(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value)
            || $user->isRole(Role::UniversityAdmin->value);
    }
}
