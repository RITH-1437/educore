<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Enrollment list (paginated on /enrollments). */
#[OA\Schema(
    schema: 'EnrollmentCollection',
    type: 'object',
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Enrollment')),
        new OA\Property(property: 'links', type: 'object'),
        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
    ]
)]
class EnrollmentCollection {}
