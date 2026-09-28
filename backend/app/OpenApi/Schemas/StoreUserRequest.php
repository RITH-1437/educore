<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Body of `POST /api/users`.
 */
#[OA\Schema(
    schema: 'StoreUserRequest',
    type: 'object',
    required: ['name', 'email', 'role_id', 'password'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Sokha Chan'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'sokha.chan@educore.kh'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, maxLength: 50, example: '+855 12 345 678'),
        new OA\Property(
            property: 'role_id',
            type: 'integer',
            format: 'int64',
            example: 5,
            description: 'Identifier of an existing role.'
        ),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'secret-password'),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'secret-password'),
    ]
)]
class StoreUserRequest {}
