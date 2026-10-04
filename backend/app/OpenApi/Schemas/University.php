<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** University representation returned by UniversityResource. */
#[OA\Schema(
    schema: 'University',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'code', type: 'string', example: 'ITC'),
        new OA\Property(property: 'name', type: 'string', example: 'Institute of Technology Cambodia'),
        new OA\Property(property: 'short_name', type: 'string', nullable: true, example: 'ITC'),
        new OA\Property(property: 'address', type: 'string', nullable: true),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
        new OA\Property(property: 'logo_key', type: 'string', nullable: true, description: 'Object storage key of the university logo.'),
        new OA\Property(property: 'website', type: 'string', nullable: true),
        new OA\Property(property: 'is_current', type: 'boolean', example: true, description: 'Exactly one university may be current.'),
        new OA\Property(property: 'departments_count', type: 'integer', nullable: true, example: 6),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class University {}
