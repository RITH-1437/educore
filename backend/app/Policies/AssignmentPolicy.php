<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Section;
use App\Models\User;
use App\Policies\Concerns\ChecksSectionTeaching;

/**
 * Assignments (`skills/assignments/SKILL.md` §8): the section's lecturers (and
 * managers) run coursework; Faculty Admin reads; students see published work
 * of their sections and touch only their own submission.
 */
class AssignmentPolicy
{
    use ChecksSectionTeaching;

    /** List a section's assignments (students see only published ones). */
    public function viewSection(User $user, Section $section): bool
    {
        return $this->staffOver($user, $section) || $this->teaches($user, $section) || $this->enrolledIn($user, $section);
    }

    public function create(User $user, Section $section): bool
    {
        return $this->manages($user) || $this->teaches($user, $section);
    }

    public function view(User $user, Assignment $assignment): bool
    {
        return $this->staffOver($user, $assignment->section)
            || $this->teaches($user, $assignment->section)
            || ($assignment->is_published && $this->enrolledIn($user, $assignment->section));
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $this->manages($user) || $this->teaches($user, $assignment->section);
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $this->update($user, $assignment);
    }

    /** See every submission and grade them. */
    public function grade(User $user, Assignment $assignment): bool
    {
        return $this->update($user, $assignment);
    }

    public function viewSubmissions(User $user, Assignment $assignment): bool
    {
        return $this->staffOver($user, $assignment->section) || $this->teaches($user, $assignment->section);
    }

    public function submit(User $user, Assignment $assignment): bool
    {
        return $assignment->is_published && $this->enrolledIn($user, $assignment->section);
    }

    public function download(User $user, AssignmentSubmission $submission): bool
    {
        return $this->viewSubmissions($user, $submission->assignment)
            || $user->student?->getKey() === $submission->enrollment->student_id;
    }
}
