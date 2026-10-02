<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GpaPeriod',
    type: 'object',
    properties: [
        new OA\Property(property: 'academic_year_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'academic_year', type: 'string'),
        new OA\Property(property: 'semester_id', type: 'integer', format: 'int64', nullable: true),
        new OA\Property(property: 'semester', type: 'string', nullable: true),
        new OA\Property(property: 'gpa', type: 'number'),
        new OA\Property(property: 'attempted_credits', type: 'number'),
        new OA\Property(property: 'earned_credits', type: 'number'),
        new OA\Property(property: 'grade_points', type: 'number'),
    ]
)]
#[OA\Schema(
    schema: 'GpaSummary',
    type: 'object',
    properties: [
        new OA\Property(property: 'semesters', type: 'array', items: new OA\Items(ref: '#/components/schemas/GpaPeriod')),
        new OA\Property(property: 'cumulative', ref: '#/components/schemas/GpaPeriod', nullable: true),
    ]
)]
class GpaSummary {}
