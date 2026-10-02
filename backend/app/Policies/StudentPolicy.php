<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->staff($user);
    }

    /**
     * Staff may view any student; a student may view only their own profile
     * (`skills/student-management/SKILL.md` §8, §11). Lecturer read access to
     * students in their sections arrives with sections and enrollment.
     */
    public function view(User $user, Student $student): bool
    {
        return ($this->staff($user) && $student->isVisibleTo($user))
            || ($user->isRole(Role::Student->value) && $student->user_id === $user->getKey());
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, Student $student): bool
    {
        return $this->manage($user);
    }

    public function changeStatus(User $user, Student $student): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, Student $student): bool
    {
        return $this->manage($user);
    }

    /**
     * Faculty Admin reads (within their faculty: `view` and the unit-scoped
     * list) but does not manage.
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
