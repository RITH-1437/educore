<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Assignment list. */
#[OA\Schema(
    schema: 'AssignmentCollection',
    type: 'object',
    properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Assignment'))]
)]
class AssignmentCollection {}
