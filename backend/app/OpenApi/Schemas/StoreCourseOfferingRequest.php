<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for creating an offering. */
#[OA\Schema(
    schema: 'StoreCourseOfferingRequest',
    type: 'object',
    required: ['course_id', 'semester_id'],
    properties: [
        new OA\Property(property: 'course_id', type: 'integer', format: 'int64', description: 'An active course.'),
        new OA\Property(property: 'semester_id', type: 'integer', format: 'int64', description: 'Not completed; unique per course.'),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published', 'open', 'closed'], default: 'draft'),
        new OA\Property(property: 'max_enrollments', type: 'integer', minimum: 1, nullable: true),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
    ]
)]
class StoreCourseOfferingRequest {}
