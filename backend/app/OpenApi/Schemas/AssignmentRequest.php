<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Create/update an assignment. */
#[OA\Schema(
    schema: 'AssignmentRequest',
    type: 'object',
    required: ['title', 'max_score', 'due_at', 'assignment_type'],
    properties: [
        new OA\Property(property: 'title', type: 'string', maxLength: 255),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'instructions', type: 'string', nullable: true),
        new OA\Property(property: 'max_score', type: 'number', format: 'float', exclusiveMinimum: 0),
        new OA\Property(property: 'due_at', type: 'string', format: 'date-time', description: 'Future, within the semester.'),
        new OA\Property(property: 'assignment_type', type: 'string', enum: ['homework', 'quiz', 'project', 'presentation', 'other']),
        new OA\Property(property: 'weight_override', type: 'number', format: 'float', minimum: 0, maximum: 100, nullable: true),
    ]
)]
class AssignmentRequest {}
