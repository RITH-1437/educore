<?php

namespace App\Policies;

use App\Models\CourseMaterial;
use App\Models\Section;
use App\Models\User;
use App\Policies\Concerns\ChecksSectionTeaching;

/**
 * Course materials (`docs/44_Course-Materials-Report.md`): the section's
 * lecturers and managers share and remove them; a Department Admin over the
 * section reads them; students of the section (open or completed enrollment)
 * read and download them.
 */
class CourseMaterialPolicy
{
    use ChecksSectionTeaching;

    public function viewSection(User $user, Section $section): bool
    {
        return $this->staffOver($user, $section) || $this->teaches($user, $section) || $this->enrolledIn($user, $section);
    }

    public function create(User $user, Section $section): bool
    {
        return $this->manages($user) || $this->teaches($user, $section);
    }

    public function view(User $user, CourseMaterial $material): bool
    {
        return $this->viewSection($user, $material->section);
    }

    public function update(User $user, CourseMaterial $material): bool
    {
        return $this->create($user, $material->section);
    }

    public function delete(User $user, CourseMaterial $material): bool
    {
        return $this->update($user, $material);
    }
}
