<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for updating a lecturer (PUT and PATCH share the rules). */
#[OA\Schema(
    schema: 'UpdateLecturerRequest',
    type: 'object',
    required: ['staff_number', 'first_name', 'last_name', 'department_id'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, description: 'Optional; updates the linked account.'),
        new OA\Property(property: 'phone', type: 'string', maxLength: 50, nullable: true, description: 'Optional; updates the linked account.'),
        new OA\Property(property: 'staff_number', type: 'string', maxLength: 50),
        new OA\Property(property: 'first_name', type: 'string', maxLength: 100),
        new OA\Property(property: 'last_name', type: 'string', maxLength: 100),
        new OA\Property(property: 'title', type: 'string', maxLength: 50, nullable: true),
        new OA\Property(property: 'department_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'position', type: 'string', maxLength: 100, nullable: true),
        new OA\Property(property: 'specialization', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'employment_type', type: 'string', enum: ['full_time', 'part_time', 'contract', 'visiting']),
    ]
)]
class UpdateLecturerRequest {}
