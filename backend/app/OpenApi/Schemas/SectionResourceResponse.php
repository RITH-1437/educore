<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Single SectionResource envelope. */
#[OA\Schema(
    schema: 'SectionResourceResponse',
    type: 'object',
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Section')]
)]
class SectionResourceResponse {}
