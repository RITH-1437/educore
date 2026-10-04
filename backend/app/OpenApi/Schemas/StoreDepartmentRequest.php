<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for creating a department. */
#[OA\Schema(
    schema: 'StoreDepartmentRequest',
    type: 'object',
    required: ['code', 'name'],
    properties: [
        new OA\Property(
            property: 'university_id',
            type: 'integer',
            format: 'int64',
            example: 1,
            description: 'Defaults to the current university.'
        ),
        new OA\Property(property: 'code', type: 'string', maxLength: 50, example: 'CSE'),
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Department of Computer Science and Engineering', description: 'Unique within the university.'),
        new OA\Property(property: 'head_name', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'description', type: 'string', nullable: true),
    ]
)]
class StoreDepartmentRequest {}
