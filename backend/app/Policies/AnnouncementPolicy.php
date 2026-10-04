<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Announcement;
use App\Models\User;
use App\Services\AnnouncementService;

/**
 * Announcements (`skills/announcements` §8): Super Admin / University Admin
 * manage all; an active lecturer writes to sections / courses they teach
 * (audience checked by `AnnouncementService`); everyone reads their own feed.
 * Department Admin reads only until unit scoping exists on the user record.
 * Students never publish.
 */
class AnnouncementPolicy
{
    /** The management list (managers: all; lecturers: own). */
    public function manageAny(User $user): bool
    {
        return $this->manages($user) || $this->activeLecturer($user);
    }

    public function create(User $user): bool
    {
        return $this->manageAny($user);
    }

    /** Edit, publish, archive, delete. */
    public function update(User $user, Announcement $announcement): bool
    {
        return $this->manages($user) || ($this->activeLecturer($user) && $announcement->author_id === $user->getKey());
    }

    public function view(User $user, Announcement $announcement): bool
    {
        return $this->update($user, $announcement)
            || app(AnnouncementService::class)->feedFor($user)->whereKey($announcement->getKey())->exists();
    }

    private function manages(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value) || $user->isRole(Role::UniversityAdmin->value);
    }

    private function activeLecturer(User $user): bool
    {
        return $user->isRole(Role::Lecturer->value) && $user->lecturer?->is_active === true;
    }
}
