<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;

/**
 * The audit trail is readable by Super Admin only (`skills/audit-logging` §8)
 * and writable by nobody through the application — there are no create,
 * update or delete abilities.
 */
class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value);
    }

    public function view(User $user, AuditLog $log): bool
    {
        return $this->viewAny($user);
    }
}
