<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Enrollment;
use App\Models\User;

/**
 * Staff see every enrollment; managers enroll anyone; a student acts only on
 * their own (`skills/enrollment/SKILL.md` §8). The student id of a
 * self-service request is taken from the account, never from the input.
 */
class EnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->staff($user);
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        return $this->staff($user) || $this->owns($user, $enrollment);
    }

    /**
     * Managers enroll any student; a student enrolls themself.
     */
    public function create(User $user): bool
    {
        return $this->manage($user) || ($user->isRole(Role::Student->value) && $user->student !== null);
    }

    public function drop(User $user, Enrollment $enrollment): bool
    {
        return $this->manage($user) || $this->owns($user, $enrollment);
    }

    public function complete(User $user, Enrollment $enrollment): bool
    {
        return $this->manage($user);
    }

    private function owns(User $user, Enrollment $enrollment): bool
    {
        return $user->isRole(Role::Student->value) && $user->student?->getKey() === $enrollment->student_id;
    }

    private function staff(User $user): bool
    {
        return $this->manage($user) || $user->isRole(Role::FacultyAdmin->value);
    }

    private function manage(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value) || $user->isRole(Role::UniversityAdmin->value);
    }
}
