<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Single UserResource response envelope. */
#[OA\Schema(
    schema: 'UserResourceResponse',
    type: 'object',
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/User')]
)]
class UserResourceResponse {}
