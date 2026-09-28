<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Non-paginated SemesterResource collection for one academic year. */
#[OA\Schema(
    schema: 'SemesterCollection',
    type: 'object',
    properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Semester'))]
)]
class SemesterCollection {}
