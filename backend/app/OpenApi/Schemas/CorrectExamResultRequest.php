<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Correct one exam result. */
#[OA\Schema(
    schema: 'CorrectExamResultRequest',
    type: 'object',
    required: ['score'],
    properties: [
        new OA\Property(property: 'score', type: 'number', format: 'float', minimum: 0, nullable: true, description: 'Not above the exam max_score; null = not sat.'),
        new OA\Property(property: 'remarks', type: 'string', nullable: true),
    ]
)]
class CorrectExamResultRequest {}
