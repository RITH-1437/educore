<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GradingScaleRequest',
    type: 'object',
    required: ['bands'],
    properties: [
        new OA\Property(property: 'bands', type: 'array', minItems: 2, maxItems: 20, items: new OA\Items(
            required: ['grade', 'min_percentage', 'grade_point'],
            properties: [
                new OA\Property(property: 'grade', type: 'string', maxLength: 5, example: 'B+'),
                new OA\Property(property: 'min_percentage', type: 'number', minimum: 0, maximum: 100, example: 80),
                new OA\Property(property: 'grade_point', type: 'number', minimum: 0, maximum: 5, example: 3.5),
                new OA\Property(property: 'is_pass', type: 'boolean', description: 'Defaults to grade_point > 0.'),
            ]
        )),
    ]
)]
class GradingScaleRequest {}
