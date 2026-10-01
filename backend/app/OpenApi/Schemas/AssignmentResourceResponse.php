<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Single assignment envelope. */
#[OA\Schema(
    schema: 'AssignmentResourceResponse',
    type: 'object',
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Assignment')]
)]
class AssignmentResourceResponse {}
