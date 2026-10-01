<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for changing a course placement in a curriculum. */
#[OA\Schema(
    schema: 'UpdateProgramCourseRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'is_required', type: 'boolean'),
        new OA\Property(property: 'suggested_semester', type: 'integer', minimum: 1, maximum: 16, nullable: true, example: 3),
    ]
)]
class UpdateProgramCourseRequest {}
