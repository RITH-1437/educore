<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Course offering representation returned by CourseOfferingResource. */
#[OA\Schema(
    schema: 'CourseOffering',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'course_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'semester_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'course', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'code', type: 'string', example: 'CS201'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'credits', type: 'number', format: 'float'),
        ]),
        new OA\Property(property: 'semester', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'code', type: 'string'),
            new OA\Property(property: 'status', type: 'string', enum: ['planned', 'open', 'closed', 'completed']),
            new OA\Property(property: 'academic_year', type: 'object', nullable: true),
        ]),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published', 'open', 'closed']),
        new OA\Property(property: 'max_enrollments', type: 'integer', nullable: true),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
        new OA\Property(property: 'sections_count', type: 'integer', description: 'On list responses.'),
        new OA\Property(property: 'total_capacity', type: 'integer', description: 'Sum of section capacities, on list responses.'),
        new OA\Property(property: 'sections', type: 'array', items: new OA\Items(ref: '#/components/schemas/Section'), description: 'On single-offering responses.'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class CourseOffering {}
