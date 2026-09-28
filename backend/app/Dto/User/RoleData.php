<?php

namespace App\Dto\User;

use App\Models\Role;

/**
 * Response data for a role.
 */
final readonly class RoleData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
    ) {}

    public static function fromModel(Role $role): self
    {
        return new self(
            id: (int) $role->id,
            name: $role->name,
            slug: $role->slug,
        );
    }
}
