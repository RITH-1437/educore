<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for updating an offering. */
#[OA\Schema(
    schema: 'UpdateCourseOfferingRequest',
    type: 'object',
    required: ['status'],
    properties: [
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published', 'open', 'closed']),
        new OA\Property(property: 'max_enrollments', type: 'integer', minimum: 1, nullable: true),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
    ]
)]
class UpdateCourseOfferingRequest {}
