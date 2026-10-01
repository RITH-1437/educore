<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Room;
use App\Models\User;

/**
 * Rooms: staff read, Super Admin / University Admin manage
 * (`skills/timetable/SKILL.md` §8; Faculty Admin has no unit scope yet).
 */
class RoomPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->staff($user);
    }

    public function view(User $user, Room $room): bool
    {
        return $this->staff($user);
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, Room $room): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, Room $room): bool
    {
        return $this->manage($user);
    }

    private function staff(User $user): bool
    {
        return $this->manage($user) || $user->isRole(Role::FacultyAdmin->value);
    }

    private function manage(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value) || $user->isRole(Role::UniversityAdmin->value);
    }
}
