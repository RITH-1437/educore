<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Role attached to a user (exposed by the API as `role`).
 */
#[OA\Schema(
    schema: 'Role',
    type: 'object',
    description: 'Role assigned to a user.',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Super Admin'),
        new OA\Property(property: 'slug', type: 'string', example: 'super-admin'),
    ]
)]
class Role {}
