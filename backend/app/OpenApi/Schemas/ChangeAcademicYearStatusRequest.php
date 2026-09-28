<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for advancing an academic-year status. */
#[OA\Schema(
    schema: 'ChangeAcademicYearStatusRequest',
    type: 'object',
    required: ['status'],
    properties: [new OA\Property(property: 'status', type: 'string', enum: ['planned', 'active', 'completed'], example: 'active')]
)]
class ChangeAcademicYearStatusRequest {}
