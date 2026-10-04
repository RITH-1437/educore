<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Exam;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Policies\Concerns\ChecksSectionTeaching;

/**
 * Examinations (`skills/examinations/SKILL.md` §8): the section's lecturers
 * (and managers) run exams and enter results; Department Admin reads; enrolled
 * students see the schedule and, once released, their own results.
 */
class ExamPolicy
{
    use ChecksSectionTeaching;

    /** List a section's exams (students: schedule + own released results). */
    public function viewSection(User $user, Section $section): bool
    {
        return $this->staffOver($user, $section) || $this->teaches($user, $section) || $this->enrolledIn($user, $section);
    }

    public function create(User $user, Section $section): bool
    {
        return $this->manages($user) || $this->teaches($user, $section);
    }

    public function view(User $user, Exam $exam): bool
    {
        return $this->viewSection($user, $exam->section);
    }

    /** Edit, delete, release and enter / correct results. */
    public function update(User $user, Exam $exam): bool
    {
        return $this->create($user, $exam->section);
    }

    public function delete(User $user, Exam $exam): bool
    {
        return $this->update($user, $exam);
    }

    /** Every student's result. */
    public function viewResults(User $user, Exam $exam): bool
    {
        return $this->staffOver($user, $exam->section) || $this->teaches($user, $exam->section);
    }

    public function viewStudent(User $user, Student $student): bool
    {
        return $this->staffOverStudent($user, $student)
            || ($user->isRole(Role::Student->value) && $user->student?->getKey() === $student->getKey());
    }
}
