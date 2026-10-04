<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\DocumentRequest;
use App\Models\User;

/**
 * Documents (`skills/documents/SKILL.md` §8): a student requests and downloads
 * their own documents; University Admin / Super Admin process every request; a
 * Department Admin processes (approve, reject, generate) the requests of their
 * faculty's students (`docs/33_Faculty-Admin-Request-Handling-Report.md`, scoped to a department since report 39).
 * Revoking an issued document stays with managers. Verification is public and
 * needs no policy.
 */
class DocumentRequestPolicy
{
    /** The full queue. */
    public function viewAny(User $user): bool
    {
        return $this->staff($user);
    }

    public function create(User $user): bool
    {
        return $user->isRole(Role::Student->value) && $user->student !== null;
    }

    /** See a request and download its document. */
    public function view(User $user, DocumentRequest $request): bool
    {
        return ($this->staff($user) && $request->isVisibleTo($user))
            || ($user->isRole(Role::Student->value) && $user->student?->getKey() === $request->student_id);
    }

    /**
     * Approve, reject, generate this request: managers, or a Department Admin when
     * the student is in their department. A student never processes a request.
     * The request is required — a class-level check would be university-wide.
     */
    public function process(User $user, DocumentRequest $request): bool
    {
        return $this->manages($user) || ($user->isRole(Role::DepartmentAdmin->value) && $request->isVisibleTo($user));
    }

    /** Whether the queue shows processing actions (each action still checks its request). */
    public function processAny(User $user): bool
    {
        return $this->manages($user) || ($user->isRole(Role::DepartmentAdmin->value) && $user->department_id !== null);
    }

    /** Revoke an issued document: an institution-level correction, managers only. */
    public function revoke(User $user): bool
    {
        return $this->manages($user);
    }

    /** Waive a document fee: managers only (`InvoicePolicy`). */
    public function waiveFee(User $user, DocumentRequest $request): bool
    {
        return $this->manages($user);
    }

    public function waiveFeeAny(User $user): bool
    {
        return $this->manages($user);
    }

    private function manages(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value) || $user->isRole(Role::UniversityAdmin->value);
    }

    private function staff(User $user): bool
    {
        return $this->manages($user) || $user->isRole(Role::DepartmentAdmin->value);
    }
}
