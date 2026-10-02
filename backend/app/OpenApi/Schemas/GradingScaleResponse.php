<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GradingScaleResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Standard'),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(properties: [
            new OA\Property(property: 'grade', type: 'string', example: 'A'),
            new OA\Property(property: 'min_percentage', type: 'number', example: 85),
            new OA\Property(property: 'max_percentage', type: 'number', example: 100),
            new OA\Property(property: 'grade_point', type: 'number', example: 4),
            new OA\Property(property: 'is_pass', type: 'boolean'),
        ])),
    ]
)]
class GradingScaleResponse {}
