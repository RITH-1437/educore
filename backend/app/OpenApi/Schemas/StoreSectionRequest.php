<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for creating a section. */
#[OA\Schema(
    schema: 'StoreSectionRequest',
    type: 'object',
    required: ['code', 'capacity'],
    properties: [
        new OA\Property(property: 'code', type: 'string', maxLength: 20, pattern: '^[A-Za-z0-9-]+$', example: 'A', description: 'Unique within the offering.'),
        new OA\Property(property: 'name', type: 'string', maxLength: 100, nullable: true),
        new OA\Property(property: 'capacity', type: 'integer', minimum: 1, maximum: 1000, example: 40),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'open', 'active', 'closed', 'archived'], default: 'draft'),
    ]
)]
class StoreSectionRequest {}
