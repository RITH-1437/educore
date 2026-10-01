<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Faculty → department nesting used by cascading selects (`skills/faculty-department/SKILL.md` §6). */
#[OA\Schema(
    schema: 'FacultyTree',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'code', type: 'string', example: 'ENG'),
        new OA\Property(property: 'name', type: 'string', example: 'Faculty of Engineering'),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(
            property: 'departments',
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
                    new OA\Property(property: 'faculty_id', type: 'integer', format: 'int64', example: 1),
                    new OA\Property(property: 'code', type: 'string', example: 'CSE'),
                    new OA\Property(property: 'name', type: 'string', example: 'Department of Computer Science and Engineering'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ]
            )
        ),
    ]
)]
class FacultyTree {}
