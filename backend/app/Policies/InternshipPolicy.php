<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Internship;
use App\Models\User;

/**
 * Internships (`skills/internship` §8): a student manages their own
 * application and reports; Super Admin / University Admin process every
 * internship and keep the companies; a Faculty Admin processes the internships
 * of their faculty's students — review, approve, reject, start, complete,
 * cancel, evaluate, review reports, edit
 * (`docs/33_Faculty-Admin-Request-Handling-Report.md`).
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
        return ($this->staff($user) && $internship->isVisibleTo($user)) || $this->owns($user, $internship);
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

    /**
     * Review, approve, reject, start, complete, cancel, evaluate, review reports,
     * edit: managers, or a Faculty Admin when the student is in their faculty.
     * The internship is required — a class-level check would be university-wide.
     */
    public function process(User $user, Internship $internship): bool
    {
        return $this->manages($user) || ($user->isRole(Role::FacultyAdmin->value) && $internship->isVisibleTo($user));
    }

    /** Host companies are shared reference data: managers only. */
    public function manageCompanies(User $user): bool
    {
        return $this->manages($user);
    }

    /** Company list for the application form and the staff screen. */
    public function viewCompanies(User $user): bool
    {
        return $this->staff($user) || $this->apply($user);
    }

    private function manages(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value) || $user->isRole(Role::UniversityAdmin->value);
    }

    private function staff(User $user): bool
    {
        return $this->manages($user) || $user->isRole(Role::FacultyAdmin->value);
    }

    private function owns(User $user, Internship $internship): bool
    {
        return $user->isRole(Role::Student->value) && $user->student?->getKey() === $internship->student_id;
    }
}
