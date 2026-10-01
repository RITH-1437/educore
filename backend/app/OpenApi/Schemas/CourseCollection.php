<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Paginated CourseResource collection. */
#[OA\Schema(
    schema: 'CourseCollection',
    type: 'object',
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Course')),
        new OA\Property(property: 'links', type: 'object', description: 'Laravel pagination links.'),
        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
    ]
)]
class CourseCollection {}
