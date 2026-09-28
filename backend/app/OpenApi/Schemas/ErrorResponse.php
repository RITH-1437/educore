<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Error payload returned for 4xx/5xx responses.
 */
#[OA\Schema(
    schema: 'ErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
    ]
)]
class ErrorResponse {}
