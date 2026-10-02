<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * User representation returned by the API (`UserResource`).
 */
#[OA\Schema(
    schema: 'User',
    type: 'object',
    description: 'A platform user account.',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Super Admin'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@educore.kh'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+855 12 345 678'),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(
            property: 'last_login_at',
            type: 'string',
            format: 'date-time',
            nullable: true,
            example: '2026-01-15T08:30:00.000000Z'
        ),
        new OA\Property(
            property: 'created_at',
            type: 'string',
            format: 'date-time',
            example: '2025-11-02T04:12:11.000000Z'
        ),
        new OA\Property(property: 'role', ref: '#/components/schemas/Role', nullable: true),
        new OA\Property(property: 'faculty_id', type: 'integer', format: 'int64', nullable: true, description: 'Faculty Admin only: the faculty they administer.'),
        new OA\Property(property: 'faculty', type: 'string', nullable: true, description: 'That faculty\'s name, when loaded.'),
    ]
)]
class User {}
