<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for updating a section. */
#[OA\Schema(
    schema: 'UpdateSectionRequest',
    type: 'object',
    required: ['code', 'capacity', 'status'],
    properties: [
        new OA\Property(property: 'code', type: 'string', maxLength: 20),
        new OA\Property(property: 'name', type: 'string', nullable: true),
        new OA\Property(property: 'capacity', type: 'integer', minimum: 1, maximum: 1000, description: 'Not below open enrollments.'),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'open', 'active', 'closed', 'archived']),
    ]
)]
class UpdateSectionRequest {}
