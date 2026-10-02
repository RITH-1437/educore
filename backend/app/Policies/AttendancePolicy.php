<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Policies\Concerns\ChecksSectionTeaching;

/**
 * Attendance (`skills/attendance/SKILL.md` §8): the section's assigned
 * lecturers record, managers may correct, Faculty Admin reads, students read
 * only their own — and never mark.
 *
 * Called as `authorize('record', [AttendanceSession::class, $section])`.
 */
class AttendancePolicy
{
    use ChecksSectionTeaching;

    public function record(User $user, Section $section): bool
    {
        return $this->manages($user) || $this->teaches($user, $section);
    }

    public function viewSection(User $user, Section $section): bool
    {
        return $this->staffOver($user, $section) || $this->teaches($user, $section);
    }

    public function viewStudent(User $user, Student $student): bool
    {
        return $this->staffOverStudent($user, $student)
            || ($user->isRole(Role::Student->value) && $user->student?->getKey() === $student->getKey());
    }
}
