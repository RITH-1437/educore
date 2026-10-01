<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for adding a course to a program curriculum. */
#[OA\Schema(
    schema: 'StoreProgramCourseRequest',
    type: 'object',
    required: ['course_id'],
    properties: [
        new OA\Property(property: 'course_id', type: 'integer', format: 'int64', example: 1, description: 'A non-archived course not already in the curriculum.'),
        new OA\Property(property: 'is_required', type: 'boolean', default: false),
        new OA\Property(property: 'suggested_semester', type: 'integer', minimum: 1, maximum: 16, nullable: true, example: 3),
    ]
)]
class StoreProgramCourseRequest {}
