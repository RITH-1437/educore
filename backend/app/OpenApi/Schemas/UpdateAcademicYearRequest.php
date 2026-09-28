<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for PUT/PATCH; required fields mirror UpdateAcademicYearRequest validation. */
#[OA\Schema(
    schema: 'UpdateAcademicYearRequest',
    type: 'object',
    required: ['code', 'name', 'start_date', 'end_date'],
    properties: [
        new OA\Property(property: 'code', type: 'string', maxLength: 50, example: '2026-2027'),
        new OA\Property(property: 'name', type: 'string', maxLength: 100, example: 'Academic Year 2026-2027'),
        new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-09-01'),
        new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2027-08-31'),
        new OA\Property(property: 'status', type: 'string', enum: ['planned', 'active', 'completed']),
        new OA\Property(property: 'is_current', type: 'boolean', example: false),
    ]
)]
class UpdateAcademicYearRequest {}
