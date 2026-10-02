<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Internship;
use App\Models\User;

/**
 * Internships (`skills/internship` §8): a student manages their own
 * application and reports; Super Admin / University Admin review, approve,
 * evaluate and manage companies; Faculty Admin reads (unit scoping is not on
 * the user record yet, so faculty-level approval would be university-wide).
 */
class InternshipPolicy
{
    /** The full queue. */
    public function viewAny(User $user): bool
    {
        return $this->staff($user);
    }

    public function view(User $user, Internship $internship): bool
    {
        return $this->staff($user) || $this->owns($user, $internship);
    }

    public function apply(User $user): bool
    {
        return $user->isRole(Role::Student->value) && $user->student !== null;
    }

    /** Edit a draft, submit, cancel before approval, add reports. */
    public function act(User $user, Internship $internship): bool
    {
        return $this->owns($user, $internship);
    }

    /** Review, approve, reject, start, complete, cancel, evaluate, review reports, edit. */
    public function process(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value) || $user->isRole(Role::UniversityAdmin->value);
    }

    /** Company list for the application form and the staff screen. */
    public function viewCompanies(User $user): bool
    {
        return $this->staff($user) || $this->apply($user);
    }

    private function staff(User $user): bool
    {
        return $this->process($user) || $user->isRole(Role::FacultyAdmin->value);
    }

    private function owns(User $user, Internship $internship): bool
    {
        return $user->isRole(Role::Student->value) && $user->student?->getKey() === $internship->student_id;
    }
}
