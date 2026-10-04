<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\ErrorLog;
use App\Models\User;

/**
 * System error logs are Super Admin only.
 *
 * A row carries an exception class, a message and the originating path, which
 * is enough to fingerprint internals — SQL fragments, file paths, hostnames.
 * `skills/audit-logging/SKILL.md` §8 says to keep audit visibility to Super
 * Admin by default, and the same reasoning applies here with more force.
 *
 * University Admin and Department Admin are therefore refused even though they
 * administer parts of the platform.
 */
class ErrorLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value);
    }

    public function view(User $user, ErrorLog $errorLog): bool
    {
        return $this->viewAny($user);
    }
}
