<?php

namespace App\Services;

use App\Dto\User\CreateUserData;
use App\Dto\User\UpdateUserData;
use App\Dto\User\UserData;
use App\Dto\User\UserListFilters;
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
            $this->audit->record('user.created', $user, after: $user->only(['name', 'email', 'role_id', 'faculty_id', 'is_active']));

            return $user;
        });

        return UserData::fromModel($user->load('role', 'faculty:id,name'), withRole: true);
    }

    public function update(User $user, UpdateUserData $data): UserData
    {
        DB::transaction(function () use ($user, $data) {
            $before = $user->getAttributes();
            $this->users->update($user, $data);
            // Role, activation and password changes are all captured here; a
            // password change is recorded as a fact, never as a value.
            $this->audit->changes('user.updated', $user->refresh(), $before);
        });

        return UserData::fromModel($user->fresh()->load('role', 'faculty:id,name'), withRole: true);
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            $this->audit->record('user.deleted', $user, $user->only(['name', 'email', 'role_id', 'is_active']));
            $this->users->delete($user);
        });
    }
}
