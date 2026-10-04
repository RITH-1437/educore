<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Body of `PUT|PATCH /api/users/{user}`.
 */
#[OA\Schema(
    schema: 'UpdateUserRequest',
    type: 'object',
    required: ['name', 'email', 'role_id'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Sokha Chan'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'sokha.chan@educore.kh'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, maxLength: 50, example: '+855 12 345 678'),
        new OA\Property(property: 'role_id', type: 'integer', format: 'int64', example: 5),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'department_id', type: 'integer', format: 'int64', nullable: true, description: 'Department a Department Admin administers (limits what they can see). Only allowed when `role_id` is the Department Admin role; omitted or null clears it.'),
        new OA\Property(
            property: 'password',
            type: 'string',
            format: 'password',
            minLength: 8,
            nullable: true,
            description: 'Optional. Leave empty to keep the current password.'
        ),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', nullable: true),
    ]
)]
class UpdateUserRequest {}
