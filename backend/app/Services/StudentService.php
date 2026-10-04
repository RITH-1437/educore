<?php

namespace App\Services;

use App\Dto\People\StudentListFilters;
use App\Dto\User\CreateUserData;
use App\Enums\Role as RoleSlug;
use App\Exceptions\BusinessRuleException;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentProgram;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Student business rules (module 9.2).
 *
 * - A student is a profile attached to a Student-role account; the account is
 *   created through `UserRepository` or an existing one is linked, in one
 *   transaction (same pattern as `LecturerService`).
 * - The program lives in `student_programs`: exactly one active row, closed —
 *   never rewritten — on transfer, graduation or withdrawal.
 * - Status changes follow `Student::TRANSITIONS`; the account may sign in only
 *   while the student is active or graduated.
 * - Deleting never removes academic history (`skills/student-management` §12).
 */
class StudentService
{
    private const PROFILE_FIELDS = [
        'student_number', 'first_name', 'last_name', 'gender', 'date_of_birth', 'address',
        'emergency_contact_name', 'emergency_contact_phone', 'national_id', 'enrollment_date',
    ];

    /**
     * Records that make up academic history; any of them blocks deletion.
     *
     * @var array<string, string>
     */
    private const HISTORY_TABLES = [
        'enrollments' => 'enrollments',
        'gpa_records' => 'GPA records',
        'document_requests' => 'document requests',
        'invoices' => 'invoices',
        'internships' => 'internships',
    ];

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly UserRepository $users,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Student>
     */
    /** `$viewer` limits a Department Admin to their department (`BelongsToDepartment`). */
    public function paginate(StudentListFilters $filters, ?User $viewer = null): LengthAwarePaginator
    {
        return Student::query()
            ->when($viewer, fn ($query) => $query->visibleTo($viewer))
            ->with(['user:id,name,email,phone,is_active', 'currentProgram.program.department:id,code,name'])
            ->search($filters->search)
            ->when($filters->status, fn ($query, $status) => $query->where('status', $status))
            ->when($filters->programId, fn ($query, $id) => $query->whereHas(
                'currentProgram',
                fn ($q) => $q->where('program_id', $id),
            ))
            ->when($filters->departmentId, fn ($query, $id) => $query->whereHas(
                'currentProgram.program',
                fn ($q) => $q->where('department_id', $id),
            ))
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->orderBy('id')
            ->paginate($filters->perPage);
    }

    /**
     * @param  array<string, mixed>  $input  validated `StoreStudentRequest` data
     */
    public function create(array $input): Student
    {
        return DB::transaction(function () use ($input) {
            $user = isset($input['user_id'])
                ? $this->existingStudentAccount((int) $input['user_id'])
                : $this->users->create(new CreateUserData(
                    name: trim($input['first_name'].' '.$input['last_name']),
                    email: (string) $input['email'],
                    roleId: $this->studentRoleId(),
                    password: (string) $input['password'],
                    phone: $input['phone'] ?? null,
                ));

            $student = Student::query()->create([
                ...Arr::only($input, self::PROFILE_FIELDS),
                'user_id' => $user->getKey(),
                'status' => Student::STATUS_ACTIVE,
            ]);

            $student->programHistory()->create([
                'program_id' => $input['program_id'],
                'started_on' => $input['program_started_on'] ?? $input['enrollment_date'] ?? now()->toDateString(),
                'status' => StudentProgram::STATUS_ACTIVE,
            ]);

            $user->update(['is_active' => true]);

            return $student->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $input  validated `UpdateStudentRequest` data
     */
    public function update(Student $student, array $input): Student
    {
        return DB::transaction(function () use ($student, $input) {
            $student->update(Arr::only($input, self::PROFILE_FIELDS));

            $account = ['name' => $student->fullName()];

            foreach (['email', 'phone'] as $field) {
                if (array_key_exists($field, $input)) {
                    $account[$field] = $input[$field];
                }
            }

            $student->user()->update($account);

            return $student->refresh();
        });
    }

    /**
     * Move the student to another status, closing the active program row on
     * graduation/withdrawal and keeping the account's sign-in in step.
     */
    public function changeStatus(Student $student, string $status, ?string $effectiveOn = null, ?string $notes = null): Student
    {
        return DB::transaction(function () use ($student, $status, $effectiveOn, $notes) {
            if (! $student->canTransitionTo($status)) {
                throw new BusinessRuleException("A {$student->status} student cannot become {$status}.");
            }

            $closing = [
                Student::STATUS_GRADUATED => StudentProgram::STATUS_COMPLETED,
                Student::STATUS_WITHDRAWN => StudentProgram::STATUS_WITHDRAWN,
            ][$status] ?? null;

            $current = $student->currentProgram;

            if ($closing !== null && $current !== null) {
                $this->close($current, $closing, $effectiveOn, $notes);
            }

            $previous = $student->status;
            $student->update(['status' => $status]);
            $student->user()->update(['is_active' => in_array($status, Student::SIGN_IN_STATUSES, true)]);
            $this->audit->record('student.status_changed', $student, ['status' => $previous], ['status' => $status], $notes);

            return $student->refresh();
        });
    }

    /**
     * Transfer the student to another program: close the active row as
     * `transferred` and open a new active row on the same date.
     */
    public function changeProgram(Student $student, int $programId, ?string $effectiveOn = null, ?string $notes = null): Student
    {
        return DB::transaction(function () use ($student, $programId, $effectiveOn, $notes) {
            if ($student->status !== Student::STATUS_ACTIVE) {
                throw new BusinessRuleException('Only an active student can change program.');
            }

            $current = $student->currentProgram;

            if ($current !== null && $current->program_id === $programId) {
                throw ValidationException::withMessages(['program_id' => 'The student is already in this program.']);
            }

            // Changing program affects enrollments (skill §10): refuse while
            // any are still open.
            $openEnrollments = DB::table('enrollments')
                ->where('student_id', $student->getKey())
                ->whereNull('deleted_at')
                ->whereIn('status', ['pending', 'confirmed'])
                ->exists();

            if ($openEnrollments) {
                throw new BusinessRuleException('The student has open enrollments; complete or drop them before changing program.');
            }

            $date = $effectiveOn ?? now()->toDateString();

            if ($current !== null) {
                $this->close($current, StudentProgram::STATUS_TRANSFERRED, $date, $notes);
            }

            $student->programHistory()->create([
                'program_id' => $programId,
                'started_on' => $date,
                'status' => StudentProgram::STATUS_ACTIVE,
                'notes' => $notes,
            ]);
            $this->audit->record('student.program_changed', $student, ['program_id' => $current?->program_id], ['program_id' => $programId], $notes);

            return $student->refresh();
        });
    }

    /**
     * Remove a profile created by mistake. Refused once any academic history
     * exists; the program-assignment rows go with the profile and the account
     * is kept but cannot sign in.
     */
    public function delete(Student $student): void
    {
        DB::transaction(function () use ($student) {
            $history = [];

            foreach (self::HISTORY_TABLES as $table => $label) {
                if (DB::table($table)->where('student_id', $student->getKey())->exists()) {
                    $history[] = $label;
                }
            }

            if ($history !== []) {
                throw new BusinessRuleException(
                    'This student has '.implode(', ', $history)
                    .' and cannot be deleted. Change their status instead.'
                );
            }

            $student->programHistory()->delete();
            $student->user()->update(['is_active' => false]);
            $student->delete();
        });
    }

    private function close(StudentProgram $row, string $status, ?string $effectiveOn, ?string $notes): void
    {
        $date = $effectiveOn ?? now()->toDateString();

        if (Carbon::parse($date)->lt($row->started_on)) {
            throw ValidationException::withMessages([
                'effective_on' => 'The date cannot be before the current program started ('.$row->started_on->toDateString().').',
            ]);
        }

        $row->update([
            'status' => $status,
            'ended_on' => $date,
            'notes' => $notes ?? $row->notes,
        ]);
    }

    private function existingStudentAccount(int $userId): User
    {
        $user = User::query()->with('role')->findOrFail($userId);

        if (! $user->isRole(RoleSlug::Student->value)) {
            throw new BusinessRuleException('Only an account with the Student role can hold a student profile.');
        }

        if (Student::query()->where('user_id', $userId)->exists()) {
            throw new BusinessRuleException('This account already has a student profile.');
        }

        return $user;
    }

    private function studentRoleId(): int
    {
        $id = Role::query()->where('slug', RoleSlug::Student->value)->value('id');

        if ($id === null) {
            throw new BusinessRuleException('The Student role is not configured. Run the role seeder first.');
        }

        return (int) $id;
    }
}
