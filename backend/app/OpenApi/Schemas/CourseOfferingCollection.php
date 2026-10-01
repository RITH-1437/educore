<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Paginated CourseOfferingResource collection. */
#[OA\Schema(
    schema: 'CourseOfferingCollection',
    type: 'object',
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/CourseOffering')),
        new OA\Property(property: 'links', type: 'object'),
        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
    ]
)]
class CourseOfferingCollection {}
