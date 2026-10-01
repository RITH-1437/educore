<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for creating a lecturer. */
#[OA\Schema(
    schema: 'StoreLecturerRequest',
    type: 'object',
    required: ['staff_number', 'first_name', 'last_name', 'department_id'],
    description: 'Provide either `user_id`, or `email` + `password` + `password_confirmation`.',
    properties: [
        new OA\Property(property: 'user_id', type: 'integer', format: 'int64', nullable: true, description: 'Existing Lecturer-role account without a profile.'),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, description: 'Required without `user_id`; unique across users.'),
        new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, description: 'Required without `user_id`.'),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
        new OA\Property(property: 'phone', type: 'string', maxLength: 50, nullable: true),
        new OA\Property(property: 'staff_number', type: 'string', maxLength: 50, example: 'LEC-0001', description: 'Unique.'),
        new OA\Property(property: 'first_name', type: 'string', maxLength: 100, example: 'Dara'),
        new OA\Property(property: 'last_name', type: 'string', maxLength: 100, example: 'Sok'),
        new OA\Property(property: 'title', type: 'string', maxLength: 50, nullable: true, example: 'Dr.'),
        new OA\Property(property: 'department_id', type: 'integer', format: 'int64', example: 1, description: 'An active (non-archived) department.'),
        new OA\Property(property: 'position', type: 'string', maxLength: 100, nullable: true),
        new OA\Property(property: 'specialization', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'employment_type', type: 'string', enum: ['full_time', 'part_time', 'contract', 'visiting'], default: 'full_time'),
    ]
)]
class StoreLecturerRequest {}
