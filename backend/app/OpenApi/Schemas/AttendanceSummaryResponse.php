<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Derived attendance counts and rate. */
#[OA\Schema(
    schema: 'AttendanceSummaryResponse',
    type: 'object',
    properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object', properties: [
        new OA\Property(property: 'enrollment_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'present', type: 'integer'),
        new OA\Property(property: 'late', type: 'integer'),
        new OA\Property(property: 'absent', type: 'integer'),
        new OA\Property(property: 'excused', type: 'integer'),
        new OA\Property(property: 'rate', type: 'number', format: 'float', nullable: true, description: 'Percentage; (present+late)/(present+late+absent).'),
    ]))]
)]
class AttendanceSummaryResponse {}
