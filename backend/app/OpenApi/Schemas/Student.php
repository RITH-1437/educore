<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Student representation returned by StudentResource. */
#[OA\Schema(
    schema: 'Student',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'user', type: 'object', nullable: true, description: 'Linked account (no credentials).', properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'name', type: 'string', example: 'Sreyneang Chea'),
            new OA\Property(property: 'email', type: 'string', format: 'email'),
            new OA\Property(property: 'phone', type: 'string', nullable: true),
            new OA\Property(property: 'is_active', type: 'boolean', description: 'False unless the student is active or graduated.'),
        ]),
        new OA\Property(property: 'student_number', type: 'string', example: 'ITC-2026-0001'),
        new OA\Property(property: 'first_name', type: 'string'),
        new OA\Property(property: 'last_name', type: 'string'),
        new OA\Property(property: 'full_name', type: 'string'),
        new OA\Property(property: 'gender', type: 'string', nullable: true, enum: ['male', 'female', 'other']),
        new OA\Property(property: 'date_of_birth', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'address', type: 'string', nullable: true),
        new OA\Property(property: 'emergency_contact_name', type: 'string', nullable: true),
        new OA\Property(property: 'emergency_contact_phone', type: 'string', nullable: true),
        new OA\Property(property: 'national_id', type: 'string', nullable: true),
        new OA\Property(property: 'enrollment_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive', 'suspended', 'graduated', 'withdrawn']),
        new OA\Property(property: 'allowed_statuses', type: 'array', items: new OA\Items(type: 'string'), description: 'Statuses this student may move to next.'),
        new OA\Property(property: 'current_program', ref: '#/components/schemas/StudentProgramPeriod', nullable: true),
        new OA\Property(property: 'program_history', type: 'array', items: new OA\Items(ref: '#/components/schemas/StudentProgramPeriod'), description: 'Present on single-student responses.'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class Student {}
