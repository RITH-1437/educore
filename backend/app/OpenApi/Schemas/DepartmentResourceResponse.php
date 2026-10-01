<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Single DepartmentResource response envelope. */
#[OA\Schema(
    schema: 'DepartmentResourceResponse',
    type: 'object',
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Department')]
)]
class DepartmentResourceResponse {}
