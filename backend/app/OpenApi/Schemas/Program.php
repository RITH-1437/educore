<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Program representation returned by ProgramResource. */
#[OA\Schema(
    schema: 'Program',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'department_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(
            property: 'department',
            type: 'object',
            nullable: true,
            description: 'Present when the relation is loaded.',
            properties: [
                new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
                new OA\Property(property: 'code', type: 'string', example: 'CSE'),
                new OA\Property(property: 'name', type: 'string', example: 'Department of Computer Science and Engineering'),
                new OA\Property(property: 'faculty_id', type: 'integer', format: 'int64', example: 1),
                new OA\Property(
                    property: 'faculty',
                    type: 'object',
                    nullable: true,
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
                        new OA\Property(property: 'code', type: 'string', example: 'ENG'),
                        new OA\Property(property: 'name', type: 'string', example: 'Faculty of Engineering'),
                    ]
                ),
            ]
        ),
        new OA\Property(property: 'code', type: 'string', example: 'BSCS'),
        new OA\Property(property: 'name', type: 'string', example: 'Bachelor of Computer Science'),
        new OA\Property(property: 'degree_level', type: 'string', enum: ['associate', 'bachelor', 'master', 'doctorate'], example: 'bachelor'),
        new OA\Property(property: 'duration_years', type: 'integer', nullable: true, example: 4),
        new OA\Property(property: 'credits_required', type: 'number', format: 'float', nullable: true, example: 144.0),
        new OA\Property(property: 'is_active', type: 'boolean', example: true, description: 'False when the program is archived.'),
        new OA\Property(
            property: 'courses',
            type: 'array',
            description: 'The program curriculum. Present on single-program responses.',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'integer', format: 'int64'),
                    new OA\Property(property: 'code', type: 'string', example: 'CS201'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'credits', type: 'number', format: 'float'),
                    new OA\Property(property: 'status', type: 'string'),
                    new OA\Property(property: 'is_required', type: 'boolean'),
                    new OA\Property(property: 'suggested_semester', type: 'integer', nullable: true),
                ]
            )
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class Program {}
