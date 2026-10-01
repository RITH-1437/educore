<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Single UniversityResource response envelope. */
#[OA\Schema(
    schema: 'UniversityResourceResponse',
    type: 'object',
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/University')]
)]
class UniversityResourceResponse {}
