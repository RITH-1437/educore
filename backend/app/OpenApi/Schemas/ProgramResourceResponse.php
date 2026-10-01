<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Single ProgramResource response envelope. */
#[OA\Schema(
    schema: 'ProgramResourceResponse',
    type: 'object',
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Program')]
)]
class ProgramResourceResponse {}
