<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Academic-year representation returned by AcademicYearResource. */
#[OA\Schema(
    schema: 'AcademicYear',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'code', type: 'string', example: '2026-2027'),
        new OA\Property(property: 'name', type: 'string', example: 'Academic Year 2026-2027'),
        new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-09-01'),
        new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2027-08-31'),
        new OA\Property(property: 'status', type: 'string', enum: ['planned', 'active', 'completed']),
        new OA\Property(property: 'status_label', type: 'string', example: 'Active'),
        new OA\Property(property: 'is_current', type: 'boolean', example: true),
        new OA\Property(property: 'semesters_count', type: 'integer', nullable: true, example: 2),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class AcademicYear {}
