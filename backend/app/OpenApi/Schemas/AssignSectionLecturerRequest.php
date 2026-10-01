<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for assigning a lecturer to a section. */
#[OA\Schema(
    schema: 'AssignSectionLecturerRequest',
    type: 'object',
    required: ['lecturer_id'],
    properties: [
        new OA\Property(property: 'lecturer_id', type: 'integer', format: 'int64', description: 'An active lecturer not yet assigned.'),
        new OA\Property(property: 'role', type: 'string', enum: ['primary', 'assistant', 'tutor'], default: 'primary', description: 'At most one primary per section.'),
    ]
)]
class AssignSectionLecturerRequest {}
