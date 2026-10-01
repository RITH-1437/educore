<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Faculty representation returned by FacultyResource. */
#[OA\Schema(
    schema: 'Faculty',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'university_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(
            property: 'university',
            type: 'object',
            nullable: true,
            description: 'Present when the relation is loaded.',
            properties: [
                new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
                new OA\Property(property: 'code', type: 'string', example: 'ITC'),
                new OA\Property(property: 'name', type: 'string', example: 'Institute of Technology Cambodia'),
            ]
        ),
        new OA\Property(property: 'code', type: 'string', example: 'ENG'),
        new OA\Property(property: 'name', type: 'string', example: 'Faculty of Engineering'),
        new OA\Property(property: 'dean_name', type: 'string', nullable: true),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean', example: true, description: 'False when the faculty is archived.'),
        new OA\Property(
            property: 'departments',
            type: 'array',
            description: 'Present when the relation is loaded.',
            items: new OA\Items(ref: '#/components/schemas/Department')
        ),
        new OA\Property(property: 'departments_count', type: 'integer', nullable: true, example: 2),
        new OA\Property(property: 'active_departments_count', type: 'integer', nullable: true, example: 2),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class Faculty {}
