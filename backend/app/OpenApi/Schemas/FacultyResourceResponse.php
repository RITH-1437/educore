<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Single FacultyResource response envelope. */
#[OA\Schema(
    schema: 'FacultyResourceResponse',
    type: 'object',
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Faculty')]
)]
class FacultyResourceResponse {}
