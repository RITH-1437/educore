<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for updating a university. */
#[OA\Schema(
    schema: 'UpdateUniversityRequest',
    type: 'object',
    required: ['code', 'name'],
    properties: [
        new OA\Property(property: 'code', type: 'string', maxLength: 50, example: 'ITC'),
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Institute of Technology Cambodia'),
        new OA\Property(property: 'short_name', type: 'string', maxLength: 100, nullable: true),
        new OA\Property(property: 'address', type: 'string', nullable: true),
        new OA\Property(property: 'phone', type: 'string', maxLength: 50, nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 150, nullable: true),
        new OA\Property(property: 'logo_key', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'website', type: 'string', format: 'uri', maxLength: 255, nullable: true),
        new OA\Property(property: 'is_current', type: 'boolean', description: 'Promoting a university to current clears the flag on the previous one.'),
    ]
)]
class UpdateUniversityRequest {}
