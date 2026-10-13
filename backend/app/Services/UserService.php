<?php

namespace App\Services;

use App\Dto\User\CreateUserData;
use App\Dto\User\UpdateUserData;
use App\Dto\User\UserData;
use App\Dto\User\UserListFilters;
use App\Enums\Role as RoleSlug;
use App\Exceptions\BusinessRuleException;
use App\Models\Role;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * User management business logic.
 *
 * Controllers authorize the action through the policy and then call one of
 * these methods; no query building or transaction handling lives in HTTP.
 */
class UserService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly UserRepository $users,
    ) {}

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function list(UserListFilters $filters): LengthAwarePaginator
    {
        return $this->users->paginate($filters);
    }

    public function create(CreateUserData $data): UserData
    {
        $user = DB::transaction(function () use ($data) {
            $user = $this->users->create($data);
            $this->audit->record('user.created', $user, after: $user->only(['name', 'email', 'role_id', 'department_id', 'is_active']));

            return $user;
        });

        return UserData::fromModel($user->load('role', 'department:id,name'), withRole: true);
    }

    /**
     * @param  User|null  $actor  the signed-in Super Admin making the change
     */
    public function update(User $user, UpdateUserData $data, ?User $actor = null): UserData
    {
        DB::transaction(function () use ($user, $data, $actor) {
            $this->assertKeepsSuperAdminAccess($user, $data, $actor);
            $this->assertProfileKeepsRole($user, $data->roleId);

            $before = $user->getAttributes();
            $this->users->update($user, $data);
            // Role, activation and password changes are all captured here; a
            // password change is recorded as a fact, never as a value.
            $this->audit->changes('user.updated', $user->refresh(), $before);
        });

        return UserData::fromModel($user->fresh()->load('role', 'department:id,name'), withRole: true);
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            if ($this->isActiveSuperAdmin($user)) {
                $this->assertAnotherActiveSuperAdmin($user);
            }

            // The Students / Lecturers screens own a profile and its history rules;
            // deleting the account underneath would leave the profile without a login.
            if (($profile = $this->profileOf($user)) !== null) {
                throw new BusinessRuleException("This account holds {$profile}. Delete the profile from the ".($user->student()->exists() ? 'Students' : 'Lecturers').' page instead; that also deactivates the account.');
            }

            $this->audit->record('user.deleted', $user, $user->only(['name', 'email', 'role_id', 'is_active']));
            $this->users->delete($user);
        });
    }

    /**
     * There is always at least one active Super Admin (only a Super Admin manages
     * users, so losing the last one locks everybody out), and a Super Admin never
     * removes their own access — deleting themselves is refused by `UserPolicy`.
     */
    private function assertKeepsSuperAdminAccess(User $user, UpdateUserData $data, ?User $actor): void
    {
        $superAdminRoleId = $this->superAdminRoleId();
        $staysActiveSuperAdmin = $data->roleId === $superAdminRoleId && ($data->isActive ?? $user->is_active);

        if (! $this->isActiveSuperAdmin($user) || $staysActiveSuperAdmin) {
            return;
        }

        if ($actor !== null && $actor->is($user)) {
            throw new BusinessRuleException('You cannot remove your own Super Admin role or deactivate your own account. Ask another Super Admin.');
        }

        $this->assertAnotherActiveSuperAdmin($user);
    }

    private function assertAnotherActiveSuperAdmin(User $user): void
    {
        // Lock every active Super Admin row, the target's included: two admins
        // demoting each other at once then queue on the same rows, and the second
        // re-reads them after the first commits instead of both passing.
        $active = User::query()
            ->where('role_id', $this->superAdminRoleId())
            ->where('is_active', true)
            ->lockForUpdate()
            ->pluck('id');

        if ($active->reject(fn ($id) => (int) $id === (int) $user->getKey())->isEmpty()) {
            throw new BusinessRuleException('This is the last active Super Admin. Make another account an active Super Admin first.');
        }
    }

    /**
     * A student profile belongs to a Student-role account and a lecturer profile
     * to a Lecturer-role account (`StudentService`, `LecturerService`); the role
     * of an account holding one cannot change underneath it.
     */
    private function assertProfileKeepsRole(User $user, int $roleId): void
    {
        if ($roleId === (int) $user->role_id || ($profile = $this->profileOf($user)) === null) {
            return;
        }

        throw new BusinessRuleException("This account holds {$profile}, which needs its current role. Delete the profile first to change the role.");
    }

    /** "the student profile of …" / "the lecturer profile of …", or null. */
    private function profileOf(User $user): ?string
    {
        if (($student = $user->student()->first()) !== null) {
            return "the student profile of {$student->fullName()} ({$student->student_number})";
        }

        if (($lecturer = $user->lecturer()->first()) !== null) {
            return "the lecturer profile of {$lecturer->fullName()} ({$lecturer->staff_number})";
        }

        return null;
    }

    private function isActiveSuperAdmin(User $user): bool
    {
        return (int) $user->role_id === $this->superAdminRoleId() && $user->is_active;
    }

    private function superAdminRoleId(): int
    {
        return (int) Role::query()->where('slug', RoleSlug::SuperAdmin->value)->value('id');
    }
}
