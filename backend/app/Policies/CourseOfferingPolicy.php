<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\User;

/**
 * Offerings and their sections, lecturer assignments and weekly class times
 * share one policy: managing a section is managing its offering.
 *
 * Super Admin and University Admin manage every offering. A Department Admin
 * manages the offerings of their department's courses
 * (`docs/46_Department-Admin-Sections-and-Schedules-Report.md`); the abilities
 * take the record, so another department's offering answers 403.
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

    /** Offering a course: managers any course, a Department Admin their department's. */
    public function create(User $user, Course $course): bool
    {
        return $this->manager($user) || ($this->departmentAdmin($user) && $course->isVisibleTo($user));
    }

    /**
     * Whether to show the "new offering" action at all; the course is checked
     * by `create` when the offering is saved.
     */
    public function createAny(User $user): bool
    {
        return $this->manager($user) || ($this->departmentAdmin($user) && $user->departmentScope() > 0);
    }

    public function update(User $user, CourseOffering $offering): bool
    {
        return $this->manager($user) || ($this->departmentAdmin($user) && $offering->isVisibleTo($user));
    }

    public function delete(User $user, CourseOffering $offering): bool
    {
        return $this->update($user, $offering);
    }

    private function staff(User $user): bool
    {
        return $this->manager($user) || $this->departmentAdmin($user);
    }

    private function manager(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value)
            || $user->isRole(Role::UniversityAdmin->value);
    }

    private function departmentAdmin(User $user): bool
    {
        return $user->isRole(Role::DepartmentAdmin->value);
    }
}
