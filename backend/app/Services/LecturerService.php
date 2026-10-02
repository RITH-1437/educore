<?php

namespace App\Services;

use App\Dto\People\LecturerListFilters;
use App\Dto\User\CreateUserData;
use App\Enums\Role as RoleSlug;
use App\Exceptions\BusinessRuleException;
use App\Models\Lecturer;
use App\Models\Role;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Lecturer business rules (module 9.3).
 *
 * A lecturer is a profile attached to a login account with the `lecturer`
 * role. Creating one either links an existing lecturer account that has no
 * profile yet, or creates the account in the same transaction (through
 * `UserRepository`, so password hashing and attributes match the Users module).
 *
 * The profile and the account move together: deactivating a lecturer marks the
 * account inactive too (`skills/lecturer-management/SKILL.md` §10), and deleting
 * the profile keeps the account but marks it inactive — removing accounts stays
 * a Super Admin action in Users management. Inactive accounts cannot sign in
 * (`LoginRequest::authenticate()`).
 */
class LecturerService
{
    private const PROFILE_FIELDS = [
        'staff_number', 'first_name', 'last_name', 'title', 'department_id',
        'position', 'specialization', 'employment_type',
    ];

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly UserRepository $users,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Lecturer>
     */
    public function paginate(LecturerListFilters $filters): LengthAwarePaginator
    {
        return Lecturer::query()
            ->with(['user:id,name,email,phone,is_active', 'department.faculty:id,code,name'])
            ->search($filters->search)
            ->when($filters->departmentId, fn ($query, $id) => $query->where('department_id', $id))
            ->when(
                $filters->facultyId,
                fn ($query, $id) => $query->whereHas('department', fn ($q) => $q->where('faculty_id', $id)),
            )
            ->when($filters->employmentType, fn ($query, $type) => $query->where('employment_type', $type))
            ->when(
                $filters->isActive !== null,
                fn ($query) => $query->where('is_active', $filters->isActive),
            )
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->orderBy('id')
            ->paginate($filters->perPage);
    }

    /**
     * @param  array<string, mixed>  $input  validated `StoreLecturerRequest` data
     */
    public function create(array $input): Lecturer
    {
        return DB::transaction(function () use ($input) {
            $user = isset($input['user_id'])
                ? $this->existingLecturerAccount((int) $input['user_id'])
                : $this->users->create(new CreateUserData(
                    name: $this->displayName($input),
                    email: (string) $input['email'],
                    roleId: $this->lecturerRoleId(),
                    password: (string) $input['password'],
                    phone: $input['phone'] ?? null,
                ));

            $lecturer = Lecturer::query()->create([
                ...Arr::only($input, self::PROFILE_FIELDS),
                'user_id' => $user->getKey(),
                'is_active' => (bool) $user->is_active,
            ]);

            return $lecturer->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $input  validated `UpdateLecturerRequest` data
     */
    public function update(Lecturer $lecturer, array $input): Lecturer
    {
        return DB::transaction(function () use ($lecturer, $input) {
            $lecturer->update(Arr::only($input, self::PROFILE_FIELDS));

            // Keep the account's display name and contact details in step
            // with the profile so the sidebar, user list and lecturer list agree.
            $account = ['name' => $this->displayName([
                'first_name' => $lecturer->first_name,
                'last_name' => $lecturer->last_name,
            ])];

            foreach (['email', 'phone'] as $field) {
                if (array_key_exists($field, $input)) {
                    $account[$field] = $input[$field];
                }
            }

            $lecturer->user()->update($account);

            return $lecturer->refresh();
        });
    }

    public function deactivate(Lecturer $lecturer): Lecturer
    {
        $lecturer = $this->setActive($lecturer, false);
        $this->audit->record('lecturer.deactivated', $lecturer, ['is_active' => true], ['is_active' => false]);

        return $lecturer;
    }

    public function reactivate(Lecturer $lecturer): Lecturer
    {
        $lecturer = $this->setActive($lecturer, true);
        $this->audit->record('lecturer.reactivated', $lecturer, ['is_active' => false], ['is_active' => true]);

        return $lecturer;
    }

    /**
     * Remove the profile. Refused while the lecturer is assigned to sections
     * (no cascade of teaching history — skill §12); the account is marked
     * inactive, not deleted.
     */
    public function delete(Lecturer $lecturer): void
    {
        DB::transaction(function () use ($lecturer) {
            if (DB::table('section_lecturers')->where('lecturer_id', $lecturer->getKey())->exists()) {
                throw new BusinessRuleException(
                    'This lecturer is assigned to sections and cannot be deleted. Deactivate them instead.'
                );
            }

            $lecturer->user()->update(['is_active' => false]);
            $lecturer->delete();
        });
    }

    private function setActive(Lecturer $lecturer, bool $active): Lecturer
    {
        return DB::transaction(function () use ($lecturer, $active) {
            $lecturer->update(['is_active' => $active]);
            $lecturer->user()->update(['is_active' => $active]);

            return $lecturer->refresh();
        });
    }

    /**
     * Validated upstream; re-checked here because it guards an identity link.
     */
    private function existingLecturerAccount(int $userId): User
    {
        $user = User::query()->with('role')->findOrFail($userId);

        if (! $user->isRole(RoleSlug::Lecturer->value)) {
            throw new BusinessRuleException('Only an account with the Lecturer role can hold a lecturer profile.');
        }

        if (Lecturer::query()->where('user_id', $userId)->exists()) {
            throw new BusinessRuleException('This account already has a lecturer profile.');
        }

        return $user;
    }

    private function lecturerRoleId(): int
    {
        $id = Role::query()->where('slug', RoleSlug::Lecturer->value)->value('id');

        if ($id === null) {
            throw new BusinessRuleException('The Lecturer role is not configured. Run the role seeder first.');
        }

        return (int) $id;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function displayName(array $input): string
    {
        return trim(($input['first_name'] ?? '').' '.($input['last_name'] ?? ''));
    }
}
