<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\User;

/**
 * Invoices & payments (`skills/invoices-payments` §8): Super Admin and
 * University Admin manage everything (there is no Finance Officer role); a
 * student reads only their own invoices and payments and never records any.
 * Department Admin and lecturers have no finance access.
 */
class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->manages($user);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $this->manages($user) || $this->owns($user, $invoice->student_id);
    }

    /** Create, edit, cancel, record and reverse payments. */
    public function manage(User $user): bool
    {
        return $this->manages($user);
    }

    public function viewStudent(User $user, Student $student): bool
    {
        return $this->manages($user) || $this->owns($user, $student->getKey());
    }

    private function manages(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value) || $user->isRole(Role::UniversityAdmin->value);
    }

    private function owns(User $user, int $studentId): bool
    {
        return $user->isRole(Role::Student->value) && $user->student?->getKey() === $studentId;
    }
}
