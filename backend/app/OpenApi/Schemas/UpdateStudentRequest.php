<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for updating a student profile. */
#[OA\Schema(
    schema: 'UpdateStudentRequest',
    type: 'object',
    required: ['student_number', 'first_name', 'last_name'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', description: 'Optional; updates the linked account.'),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
        new OA\Property(property: 'student_number', type: 'string', pattern: '^[A-Za-z0-9-]{4,50}$'),
        new OA\Property(property: 'first_name', type: 'string'),
        new OA\Property(property: 'last_name', type: 'string'),
        new OA\Property(property: 'gender', type: 'string', nullable: true, enum: ['male', 'female', 'other']),
        new OA\Property(property: 'date_of_birth', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'address', type: 'string', nullable: true),
        new OA\Property(property: 'emergency_contact_name', type: 'string', nullable: true),
        new OA\Property(property: 'emergency_contact_phone', type: 'string', nullable: true),
        new OA\Property(property: 'national_id', type: 'string', nullable: true),
        new OA\Property(property: 'enrollment_date', type: 'string', format: 'date', nullable: true),
    ]
)]
class UpdateStudentRequest {}
