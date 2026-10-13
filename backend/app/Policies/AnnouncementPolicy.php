<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Announcement;
use App\Models\User;
use App\Services\AnnouncementService;

/**
 * Announcements (`skills/announcements` §8): Super Admin / University Admin
 * manage all; a Department Admin with a department writes to it and its
 * programs, courses and sections, an active lecturer to sections / courses
 * they teach (audiences checked by `AnnouncementService`, report 51); everyone
 * reads their own feed. Students never publish.
 */
class AnnouncementPolicy
{
    /** The management list (managers: all; Department Admins and lecturers: own). */
    public function manageAny(User $user): bool
    {
        return app(AnnouncementService::class)->canWrite($user);
    }

    public function create(User $user): bool
    {
        return $this->manageAny($user);
    }

    /** Edit, schedule, publish, archive, delete. */
    public function update(User $user, Announcement $announcement): bool
    {
        return $this->manages($user) || ($announcement->author_id === $user->getKey() && $this->manageAny($user));
    }

    /** Mark it read: a published announcement in the user's feed. */
    public function read(User $user, Announcement $announcement): bool
    {
        return $announcement->publish_state === Announcement::STATE_PUBLISHED
            && app(AnnouncementService::class)->feedFor($user)->whereKey($announcement->getKey())->exists();
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
}
