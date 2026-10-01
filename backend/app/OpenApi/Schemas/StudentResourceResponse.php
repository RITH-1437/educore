<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Single StudentResource response envelope. */
#[OA\Schema(
    schema: 'StudentResourceResponse',
    type: 'object',
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Student')]
)]
class StudentResourceResponse {}
