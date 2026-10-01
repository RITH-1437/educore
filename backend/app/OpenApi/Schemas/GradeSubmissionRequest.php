<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Grade a submission. */
#[OA\Schema(
    schema: 'GradeSubmissionRequest',
    type: 'object',
    required: ['score'],
    properties: [
        new OA\Property(property: 'score', type: 'number', format: 'float', minimum: 0, description: 'Not above the assignment max_score.'),
        new OA\Property(property: 'feedback', type: 'string', nullable: true),
    ]
)]
class GradeSubmissionRequest {}
