<?php

namespace App\Repositories;

use App\Dto\User\CreateUserData;
use App\Dto\User\UpdateUserData;
use App\Dto\User\UserListFilters;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Data access for users.
 *
 * Owns the listing query (search + role filter + pagination) so the web and
 * API controllers share one definition, and keeps all Eloquent writes for the
 * `users` table in a single place.
 */
class UserRepository
{
    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(UserListFilters $filters): LengthAwarePaginator
    {
        return User::query()
            ->with('role')
            ->when($filters->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%");
                });
            })
            ->when($filters->roleId, fn ($query, $roleId) => $query->where('role_id', $roleId))
            ->when($filters->role, fn ($query, $role) => $query->whereHas('role', fn ($q) => $q->where('slug', $role)))
            ->orderByDesc('id')
            ->paginate($filters->perPage);
    }

    public function create(CreateUserData $data): User
    {
        return User::query()->create($data->toAttributes());
    }

    public function update(User $user, UpdateUserData $data): User
    {
        $user->update($data->toAttributes());

        return $user;
    }

    /**
     * Soft delete the user and revoke every issued API token.
     */
    public function delete(User $user): void
    {
        $user->tokens()->delete();

        $user->delete();
    }

    public function recordLogin(User $user): void
    {
        $user->forceFill(['last_login_at' => now()])->save();
    }
}
