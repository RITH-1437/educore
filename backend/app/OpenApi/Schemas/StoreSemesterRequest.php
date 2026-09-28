<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for creating a semester. */
#[OA\Schema(
    schema: 'StoreSemesterRequest',
    type: 'object',
    required: ['name', 'code', 'sequence'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 50, example: 'Fall Semester'),
        new OA\Property(property: 'code', type: 'string', maxLength: 20, example: 'FALL'),
        new OA\Property(property: 'sequence', type: 'integer', minimum: 1, maximum: 20, example: 1),
        new OA\Property(property: 'start_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'end_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'enrollment_start', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'enrollment_end', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'exam_start', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'exam_end', type: 'string', format: 'date', nullable: true),
        new OA\Property(
            property: 'status',
            type: 'string',
            enum: ['planned', 'open', 'closed', 'completed'],
            description: 'Optional initial status. The request validator accepts the enum; the service rejects `completed` on creation with 409.'
        ),
    ]
)]
class StoreSemesterRequest {}
