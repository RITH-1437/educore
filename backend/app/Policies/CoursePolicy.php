<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->viewCatalog($user);
    }

    /** A Department Admin only sees their own department's records. */
    public function view(User $user, Course $course): bool
    {
        return $this->viewCatalog($user) && $course->isVisibleTo($user);
    }

    public function create(User $user): bool
    {
        return $this->manageCatalog($user);
    }

    public function update(User $user, Course $course): bool
    {
        return $this->manageCatalog($user);
    }

    public function archive(User $user, Course $course): bool
    {
        return $this->manageCatalog($user);
    }

    public function delete(User $user, Course $course): bool
    {
        return $this->manageCatalog($user);
    }

    /**
     * Department Admin reads the catalog but cannot change it: the user row has no
     * department scope yet, so "manage within scope"
     * (`skills/course-management/SKILL.md` §8) cannot be enforced.
     * Lecturer and student catalog views arrive with their own dashboards.
     */
    private function viewCatalog(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value)
            || $user->isRole(Role::UniversityAdmin->value)
            || $user->isRole(Role::DepartmentAdmin->value);
    }

    private function manageCatalog(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value)
            || $user->isRole(Role::UniversityAdmin->value);
    }
}
