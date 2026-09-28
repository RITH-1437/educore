<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Semester representation returned by SemesterResource. */
#[OA\Schema(
    schema: 'Semester',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'academic_year_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Fall Semester'),
        new OA\Property(property: 'code', type: 'string', example: 'FALL'),
        new OA\Property(property: 'sequence', type: 'integer', example: 1),
        new OA\Property(property: 'start_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'end_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'enrollment_start', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'enrollment_end', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'exam_start', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'exam_end', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['planned', 'open', 'closed', 'completed']),
        new OA\Property(property: 'status_label', type: 'string', example: 'Open'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class Semester {}
