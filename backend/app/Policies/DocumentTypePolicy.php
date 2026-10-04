<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\DocumentType;
use App\Models\User;

/**
 * Document types policy (`docs/40_Document-Fee-Billing-and-Type-Management-Report.md`).
 *
 * Super Admin and University Admin manage document type definitions and fee schedules;
 * authenticated users may read document types.
 */
class DocumentTypePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DocumentType $type): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->manages($user);
    }

    public function update(User $user, DocumentType $type): bool
    {
        return $this->manages($user);
    }

    public function delete(User $user, DocumentType $type): bool
    {
        return $this->manages($user);
    }

    private function manages(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value) || $user->isRole(Role::UniversityAdmin->value);
    }
}
