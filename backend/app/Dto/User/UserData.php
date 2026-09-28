<?php

namespace App\Dto\User;

use App\Models\User;

/**
 * Response data for a user.
 *
 * Services return this object instead of a model so controllers and API
 * resources stay independent of the database representation.
 */
final readonly class UserData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?string $phone,
        public bool $isActive,
        public ?string $lastLoginAt,
        public ?string $createdAt,
        public ?RoleData $role = null,
    ) {}

    public static function fromModel(User $user, bool $withRole = false): self
    {
        return new self(
            id: (int) $user->id,
            name: $user->name,
            email: $user->email,
            phone: $user->phone,
            isActive: (bool) $user->is_active,
            lastLoginAt: $user->last_login_at?->toISOString(),
            createdAt: $user->created_at?->toISOString(),
            role: $withRole && $user->role !== null ? RoleData::fromModel($user->role) : null,
        );
    }

    public function hasRole(): bool
    {
        return $this->role instanceof RoleData;
    }
}
