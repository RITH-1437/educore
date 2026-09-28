<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Paginated list of users.
 */
#[OA\Schema(
    schema: 'UserCollection',
    type: 'object',
    description: 'Paginated collection of users.',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/User')
        ),
        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
    ]
)]
class UserCollection {}
