<?php

namespace App\Services;

use App\Enums\Role as RoleSlug;
use App\Models\DocumentRequest;
use App\Models\Internship;
use App\Models\Student;
use App\Models\User;
use App\Notifications\DocumentRequestSubmitted;
use App\Notifications\InternshipSubmitted;
use App\Support\DepartmentScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Tells the staff who must act on a student's request that it is waiting
 * (`docs/43_Staff-Request-Notices-Report.md`). The recipients are exactly the
 * people allowed to process it: active Super Admins and University Admins,
 * plus active Department Admins of a department the student belongs to (the
 * same rule as `DepartmentScope`). Delivery follows each recipient's channel
 * preferences; notifications go out only after the request commits.
 */
class StaffNotifier
{
    public function documentRequested(DocumentRequest $request): void
    {
        Notification::send($this->handlersOf($request->student), new DocumentRequestSubmitted($request));
    }

    public function internshipSubmitted(Internship $internship): void
    {
        Notification::send($this->handlersOf($internship->student), new InternshipSubmitted($internship));
    }

    /** @return Collection<int, User> */
    public function handlersOf(Student $student): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query
                ->whereHas('role', fn (Builder $role) => $role->whereIn('slug', [RoleSlug::SuperAdmin->value, RoleSlug::UniversityAdmin->value]))
                ->orWhere(fn (Builder $admins) => $admins
                    ->whereHas('role', fn (Builder $role) => $role->where('slug', RoleSlug::DepartmentAdmin->value))
                    ->whereIn('department_id', DepartmentScope::departmentIdsOf($student->getKey()))))
            ->get();
    }
}
