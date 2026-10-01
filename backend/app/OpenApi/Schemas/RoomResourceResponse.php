<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Single room envelope. */
#[OA\Schema(
    schema: 'RoomResourceResponse',
    type: 'object',
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Room')]
)]
class RoomResourceResponse {}
