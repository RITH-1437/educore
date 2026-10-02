<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GradingConfig',
    type: 'object',
    properties: [
        new OA\Property(property: 'course_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'attendance_weight', type: 'number', example: 10),
        new OA\Property(property: 'assignment_weight', type: 'number', example: 25),
        new OA\Property(property: 'midterm_weight', type: 'number', example: 20),
        new OA\Property(property: 'final_weight', type: 'number', example: 40),
        new OA\Property(property: 'practical_weight', type: 'number', example: 5),
        new OA\Property(property: 'is_default', type: 'boolean', description: 'True when no weights are saved for the course.'),
    ]
)]
#[OA\Schema(
    schema: 'GradingConfigRequest',
    type: 'object',
    required: ['attendance_weight', 'assignment_weight', 'midterm_weight', 'final_weight', 'practical_weight'],
    description: 'Weights 0–100 that add up to exactly 100.',
    properties: [
        new OA\Property(property: 'attendance_weight', type: 'number', minimum: 0, maximum: 100),
        new OA\Property(property: 'assignment_weight', type: 'number', minimum: 0, maximum: 100),
        new OA\Property(property: 'midterm_weight', type: 'number', minimum: 0, maximum: 100),
        new OA\Property(property: 'final_weight', type: 'number', minimum: 0, maximum: 100),
        new OA\Property(property: 'practical_weight', type: 'number', minimum: 0, maximum: 100),
    ]
)]
class GradingConfig {}
