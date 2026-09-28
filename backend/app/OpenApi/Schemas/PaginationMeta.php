<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Pagination metadata returned with paginated collections.
 */
#[OA\Schema(
    schema: 'PaginationMeta',
    type: 'object',
    description: 'Pagination metadata produced by Laravel resource collections.',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'from', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'last_page', type: 'integer', example: 3),
        new OA\Property(property: 'per_page', type: 'integer', example: 15),
        new OA\Property(property: 'to', type: 'integer', nullable: true, example: 15),
        new OA\Property(property: 'total', type: 'integer', example: 42),
    ]
)]
class PaginationMeta {}
