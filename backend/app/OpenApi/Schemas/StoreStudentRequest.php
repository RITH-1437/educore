<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for creating a student. */
#[OA\Schema(
    schema: 'StoreStudentRequest',
    type: 'object',
    required: ['student_number', 'first_name', 'last_name', 'program_id'],
    description: 'Provide either `user_id`, or `email` + `password` + `password_confirmation`.',
    properties: [
        new OA\Property(property: 'user_id', type: 'integer', format: 'int64', nullable: true, description: 'Existing Student-role account without a profile.'),
        new OA\Property(property: 'email', type: 'string', format: 'email', description: 'Required without `user_id`; unique across users.'),
        new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
        new OA\Property(property: 'student_number', type: 'string', pattern: '^[A-Za-z0-9-]{4,50}$', example: 'ITC-2026-0001', description: 'Unique, stable student ID.'),
        new OA\Property(property: 'first_name', type: 'string', maxLength: 100),
        new OA\Property(property: 'last_name', type: 'string', maxLength: 100),
        new OA\Property(property: 'gender', type: 'string', nullable: true, enum: ['male', 'female', 'other']),
        new OA\Property(property: 'date_of_birth', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'address', type: 'string', nullable: true),
        new OA\Property(property: 'emergency_contact_name', type: 'string', nullable: true),
        new OA\Property(property: 'emergency_contact_phone', type: 'string', nullable: true),
        new OA\Property(property: 'national_id', type: 'string', nullable: true, description: 'Unique when given.'),
        new OA\Property(property: 'enrollment_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'program_id', type: 'integer', format: 'int64', description: 'An active program.'),
        new OA\Property(property: 'program_started_on', type: 'string', format: 'date', nullable: true, description: 'Defaults to `enrollment_date`, then today.'),
    ]
)]
class StoreStudentRequest {}
