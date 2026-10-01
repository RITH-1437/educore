<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Department representation returned by DepartmentResource. */
#[OA\Schema(
    schema: 'Department',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'faculty_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(
            property: 'faculty',
            type: 'object',
            nullable: true,
            description: 'Present when the relation is loaded.',
            properties: [
                new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
                new OA\Property(property: 'code', type: 'string', example: 'ENG'),
                new OA\Property(property: 'name', type: 'string', example: 'Faculty of Engineering'),
                new OA\Property(property: 'university_id', type: 'integer', format: 'int64', example: 1),
            ]
        ),
        new OA\Property(property: 'code', type: 'string', example: 'CSE'),
        new OA\Property(property: 'name', type: 'string', example: 'Department of Computer Science and Engineering'),
        new OA\Property(property: 'head_name', type: 'string', nullable: true),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean', example: true, description: 'False when the department is archived.'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class Department {}
