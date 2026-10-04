<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for updating a department. */
#[OA\Schema(
    schema: 'UpdateDepartmentRequest',
    type: 'object',
    required: ['code', 'name'],
    properties: [
        new OA\Property(
            property: 'university_id',
            type: 'integer',
            format: 'int64',
            example: 1,
            description: 'Moving a department to another university keeps its children valid because they reference the department id.'
        ),
        new OA\Property(property: 'code', type: 'string', maxLength: 50, example: 'CSE'),
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Department of Computer Science and Engineering'),
        new OA\Property(property: 'head_name', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'description', type: 'string', nullable: true),
    ]
)]
class UpdateDepartmentRequest {}
