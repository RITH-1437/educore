<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Response of the public health endpoint.
 */
#[OA\Schema(
    schema: 'HealthResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'status', type: 'string', example: 'ok'),
    ]
)]
class HealthResponse {}
