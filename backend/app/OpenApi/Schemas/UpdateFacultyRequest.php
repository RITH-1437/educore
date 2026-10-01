<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for updating a faculty. */
#[OA\Schema(
    schema: 'UpdateFacultyRequest',
    type: 'object',
    required: ['university_id', 'code', 'name'],
    properties: [
        new OA\Property(property: 'university_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'code', type: 'string', maxLength: 50, example: 'ENG'),
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Faculty of Engineering'),
        new OA\Property(property: 'dean_name', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'description', type: 'string', nullable: true),
    ]
)]
class UpdateFacultyRequest {}
