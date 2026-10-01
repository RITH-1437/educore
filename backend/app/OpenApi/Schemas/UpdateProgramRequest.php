<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for updating a program (PUT and PATCH share the rules). */
#[OA\Schema(
    schema: 'UpdateProgramRequest',
    type: 'object',
    required: ['code', 'name', 'degree_level'],
    properties: [
        new OA\Property(property: 'department_id', type: 'integer', format: 'int64', example: 1, description: 'Optional; moves the program to another active department.'),
        new OA\Property(property: 'code', type: 'string', maxLength: 50, example: 'BSCS'),
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Bachelor of Computer Science', description: 'Unique within the (resulting) department.'),
        new OA\Property(property: 'degree_level', type: 'string', enum: ['associate', 'bachelor', 'master', 'doctorate'], example: 'bachelor'),
        new OA\Property(property: 'duration_years', type: 'integer', minimum: 1, maximum: 10, nullable: true, example: 4),
        new OA\Property(property: 'credits_required', type: 'number', format: 'float', minimum: 0, nullable: true, example: 144.0),
    ]
)]
class UpdateProgramRequest {}
