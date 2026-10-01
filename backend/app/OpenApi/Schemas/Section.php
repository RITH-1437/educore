<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Section representation returned by SectionResource. */
#[OA\Schema(
    schema: 'Section',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'course_offering_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'code', type: 'string', example: 'A'),
        new OA\Property(property: 'name', type: 'string', nullable: true),
        new OA\Property(property: 'capacity', type: 'integer', example: 40),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'open', 'active', 'closed', 'archived']),
        new OA\Property(property: 'enrolled', type: 'integer', description: 'Open (pending/confirmed) enrollments, when computed.'),
        new OA\Property(property: 'offering', type: 'object', nullable: true, description: 'Present on single-section responses.'),
        new OA\Property(property: 'lecturers', type: 'array', items: new OA\Items(type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'staff_number', type: 'string'),
            new OA\Property(property: 'full_name', type: 'string'),
            new OA\Property(property: 'is_active', type: 'boolean'),
            new OA\Property(property: 'role', type: 'string', enum: ['primary', 'assistant', 'tutor']),
        ])),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class Section {}
