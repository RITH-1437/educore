<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Single ErrorLog wrapped in the standard `data` envelope. */
#[OA\Schema(
    schema: 'ErrorLogResourceResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/ErrorLog'),
    ]
)]
class ErrorLogResourceResponse {}
