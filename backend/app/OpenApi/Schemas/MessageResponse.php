<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Generic `{ "message": "..." }` payload.
 */
#[OA\Schema(
    schema: 'MessageResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Logged out.'),
    ]
)]
class MessageResponse {}
