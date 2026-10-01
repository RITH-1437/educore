<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Create / update an exam. */
#[OA\Schema(
    schema: 'ExamRequest',
    type: 'object',
    required: ['exam_type', 'title', 'weight', 'max_score'],
    properties: [
        new OA\Property(property: 'exam_type', type: 'string', enum: ['midterm', 'final', 'quiz', 'practical', 'other']),
        new OA\Property(property: 'title', type: 'string', maxLength: 255),
        new OA\Property(property: 'weight', type: 'number', format: 'float', minimum: 0, maximum: 100, description: 'Exam weights of a section total at most 100.'),
        new OA\Property(property: 'max_score', type: 'number', format: 'float', minimum: 0, exclusiveMinimum: true),
        new OA\Property(property: 'scheduled_date', type: 'string', format: 'date', nullable: true, description: 'Within the semester; required with times.'),
        new OA\Property(property: 'start_time', type: 'string', example: '09:00', nullable: true),
        new OA\Property(property: 'end_time', type: 'string', example: '11:00', nullable: true),
        new OA\Property(property: 'location', type: 'string', nullable: true),
    ]
)]
class ExamRequest {}
