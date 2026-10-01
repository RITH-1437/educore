<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;

/**
 * Attendance (`skills/attendance/SKILL.md` §8): the section's assigned
 * lecturers record, managers may correct, Faculty Admin reads, students read
 * only their own — and never mark.
 *
 * Called as `authorize('record', [AttendanceSession::class, $section])`.
 */
class AttendancePolicy
{
    public function record(User $user, Section $section): bool
    {
        return $this->manage($user) || $this->teaches($user, $section);
    }

    public function viewSection(User $user, Section $section): bool
    {
        return $this->manage($user) || $user->isRole(Role::FacultyAdmin->value) || $this->teaches($user, $section);
    }

    public function viewStudent(User $user, Student $student): bool
    {
        return $this->manage($user)
            || $user->isRole(Role::FacultyAdmin->value)
            || ($user->isRole(Role::Student->value) && $user->student?->getKey() === $student->getKey());
    }

    private function teaches(User $user, Section $section): bool
    {
        $lecturer = $user->isRole(Role::Lecturer->value) ? $user->lecturer : null;

        return $lecturer !== null && $lecturer->is_active && $section->lecturers()->whereKey($lecturer->getKey())->exists();
    }

    private function manage(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value) || $user->isRole(Role::UniversityAdmin->value);
    }
}
