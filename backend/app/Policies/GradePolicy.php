<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Course;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Policies\Concerns\ChecksSectionTeaching;

/**
 * Grades & GPA (`skills/grading-gpa/SKILL.md` §8): the section's lecturers
 * compute and submit, University Admin / Super Admin approve, return and
 * manage the scale and course weights, Department Admin reads, a student sees
 * only their own approved grades and GPA.
 */
class GradePolicy
{
    use ChecksSectionTeaching;

    /** A section's grade sheet. */
    public function viewSection(User $user, Section $section): bool
    {
        return $this->staffOver($user, $section) || $this->teaches($user, $section);
    }

    /** Compute drafts and submit them. */
    public function grade(User $user, Section $section): bool
    {
        return $this->manages($user) || $this->teaches($user, $section);
    }

    /** Approve or return a section's grades. */
    public function approve(User $user): bool
    {
        return $this->manages($user);
    }

    /** Unlock finalized grades: Super Admin only. */
    public function reopen(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value);
    }

    /** Sections awaiting approval. */
    public function viewAny(User $user): bool
    {
        return $this->staff($user);
    }

    public function viewStudent(User $user, Student $student): bool
    {
        return $this->staffOverStudent($user, $student)
            || ($user->isRole(Role::Student->value) && $user->student?->getKey() === $student->getKey());
    }

    /** The grading scale is public to every signed-in role. */
    public function viewScale(User $user): bool
    {
        return true;
    }

    /** Edit the grading scale and course weights. */
    public function configure(User $user): bool
    {
        return $this->manages($user);
    }

    /** Read a course's weights. */
    public function viewConfig(User $user, Course $course): bool
    {
        return $this->staff($user) && $course->isVisibleTo($user);
    }
}
