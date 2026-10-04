<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Lecturer representation returned by LecturerResource. */
#[OA\Schema(
    schema: 'Lecturer',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', format: 'int64', example: 12),
        new OA\Property(
            property: 'user',
            type: 'object',
            nullable: true,
            description: 'The linked login account. Present when loaded.',
            properties: [
                new OA\Property(property: 'id', type: 'integer', format: 'int64'),
                new OA\Property(property: 'name', type: 'string', example: 'Dara Sok'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'dara.sok@educore.kh'),
                new OA\Property(property: 'phone', type: 'string', nullable: true),
                new OA\Property(property: 'is_active', type: 'boolean'),
            ]
        ),
        new OA\Property(property: 'staff_number', type: 'string', example: 'LEC-0001'),
        new OA\Property(property: 'first_name', type: 'string', example: 'Dara'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Sok'),
        new OA\Property(property: 'full_name', type: 'string', example: 'Dr. Dara Sok'),
        new OA\Property(property: 'title', type: 'string', nullable: true, example: 'Dr.'),
        new OA\Property(property: 'department_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(
            property: 'department',
            type: 'object',
            nullable: true,
            description: 'Present when loaded.',
            properties: [
                new OA\Property(property: 'id', type: 'integer', format: 'int64'),
                new OA\Property(property: 'code', type: 'string', example: 'CSE'),
                new OA\Property(property: 'name', type: 'string'),
            ]
        ),
        new OA\Property(property: 'position', type: 'string', nullable: true, example: 'Senior Lecturer'),
        new OA\Property(property: 'specialization', type: 'string', nullable: true, example: 'Distributed systems'),
        new OA\Property(property: 'employment_type', type: 'string', enum: ['full_time', 'part_time', 'contract', 'visiting']),
        new OA\Property(property: 'is_active', type: 'boolean', description: 'Mirrored onto the linked account `is_active`.'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class Lecturer {}
