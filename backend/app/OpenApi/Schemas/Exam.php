<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Exam representation. */
#[OA\Schema(
    schema: 'Exam',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'section_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'exam_type', type: 'string', enum: ['midterm', 'final', 'quiz', 'practical', 'other']),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'weight', type: 'number', format: 'float', example: 30),
        new OA\Property(property: 'max_score', type: 'number', format: 'float', example: 100),
        new OA\Property(property: 'scheduled_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'start_time', type: 'string', example: '09:00', nullable: true),
        new OA\Property(property: 'end_time', type: 'string', example: '11:00', nullable: true),
        new OA\Property(property: 'location', type: 'string', nullable: true),
        new OA\Property(property: 'is_published', type: 'boolean', description: 'Results released to students.'),
        new OA\Property(property: 'results_count', type: 'integer', description: 'Staff/lecturer views.'),
    ]
)]
class Exam {}
