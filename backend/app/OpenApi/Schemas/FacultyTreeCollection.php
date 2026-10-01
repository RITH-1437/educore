<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Faculty → department tree collection. */
#[OA\Schema(
    schema: 'FacultyTreeCollection',
    type: 'object',
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/FacultyTree')),
    ]
)]
class FacultyTreeCollection {}
