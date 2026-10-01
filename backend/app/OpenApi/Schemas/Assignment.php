<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Assignment representation. */
#[OA\Schema(
    schema: 'Assignment',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'section_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'instructions', type: 'string', nullable: true),
        new OA\Property(property: 'max_score', type: 'number', format: 'float', example: 100),
        new OA\Property(property: 'due_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'past_due', type: 'boolean'),
        new OA\Property(property: 'assignment_type', type: 'string', enum: ['homework', 'quiz', 'project', 'presentation', 'other']),
        new OA\Property(property: 'weight_override', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'is_published', type: 'boolean'),
        new OA\Property(property: 'published_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'submissions_count', type: 'integer', description: 'Staff/lecturer views.'),
        new OA\Property(property: 'graded_count', type: 'integer', description: 'Staff/lecturer views.'),
        new OA\Property(property: 'my_submission', ref: '#/components/schemas/Submission', nullable: true, description: 'Student views.'),
    ]
)]
class Assignment {}
