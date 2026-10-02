<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\DocumentRequest;
use App\Models\User;

/**
 * Documents (`skills/documents/SKILL.md` §8): a student requests and downloads
 * their own documents; University Admin / Super Admin process them; Faculty
 * Admin reads (unit scoping waits for unit assignment on the user record).
 * Verification is public and needs no policy.
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

    /** Approve, reject, generate, revoke. A student never processes a request. */
    public function process(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value) || $user->isRole(Role::UniversityAdmin->value);
    }

    private function staff(User $user): bool
    {
        return $this->process($user) || $user->isRole(Role::FacultyAdmin->value);
    }
}
