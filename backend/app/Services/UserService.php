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
        $user = DB::transaction(fn () => $this->users->create($data));

        return UserData::fromModel($user->load('role'), withRole: true);
    }

    public function update(User $user, UpdateUserData $data): UserData
    {
        DB::transaction(fn () => $this->users->update($user, $data));

        return UserData::fromModel($user->fresh()->load('role'), withRole: true);
    }

    public function delete(User $user): void
    {
        DB::transaction(fn () => $this->users->delete($user));
    }
}
