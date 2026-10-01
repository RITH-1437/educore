<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for adding a prerequisite to a course. */
#[OA\Schema(
    schema: 'StoreCoursePrerequisiteRequest',
    type: 'object',
    required: ['prerequisite_course_id'],
    properties: [
        new OA\Property(property: 'prerequisite_course_id', type: 'integer', format: 'int64', example: 2, description: 'Must exist, not be the course itself, not be archived, not already be set, and not create a cycle.'),
        new OA\Property(property: 'is_strict', type: 'boolean', default: true, description: 'Strict prerequisites must be completed before enrollment.'),
    ]
)]
class StoreCoursePrerequisiteRequest {}
