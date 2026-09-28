<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Raw user model returned by `GET /api/user`.
 */
#[OA\Schema(
    schema: 'AuthenticatedUser',
    type: 'object',
    description: 'The user attached to the current token, as returned by the raw model (password and remember token are hidden).',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Super Admin'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@educore.kh'),
        new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'role_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+855 12 345 678'),
        new OA\Property(property: 'avatar_key', type: 'string', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'last_login_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
class AuthenticatedUser {}
