<?php

namespace App\Policies\Concerns;

use App\Enums\Role;
use App\Models\Section;
use App\Models\User;

/**
 * Shared "who runs this section" checks for section-scoped academic work
 * (attendance, assignments, exams).
 */
trait ChecksSectionTeaching
{
    /** An active lecturer assigned to the section. */
    protected function teaches(User $user, Section $section): bool
    {
        $lecturer = $user->isRole(Role::Lecturer->value) ? $user->lecturer : null;

        return $lecturer !== null && $lecturer->is_active && $section->lecturers()->whereKey($lecturer->getKey())->exists();
    }

    /** Super Admin or University Admin. */
    protected function manages(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value) || $user->isRole(Role::UniversityAdmin->value);
    }

    /** Managers plus Faculty Admin (read access). */
    protected function staff(User $user): bool
    {
        return $this->manages($user) || $user->isRole(Role::FacultyAdmin->value);
    }

    /** The signed-in student holds an open/completed enrollment in the section. */
    protected function enrolledIn(User $user, Section $section): bool
    {
        $student = $user->isRole(Role::Student->value) ? $user->student : null;

        return $student !== null && $section->enrollments()
            ->where('student_id', $student->getKey())
            ->whereIn('status', ['pending', 'confirmed', 'completed'])
            ->exists();
    }
}
