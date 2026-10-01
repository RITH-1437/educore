<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Enrollment representation returned by EnrollmentResource. */
#[OA\Schema(
    schema: 'Enrollment',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'student_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'student', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'student_number', type: 'string'),
            new OA\Property(property: 'full_name', type: 'string'),
        ]),
        new OA\Property(property: 'section_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'section', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'code', type: 'string'),
            new OA\Property(property: 'offering_id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'course', type: 'object', properties: [
                new OA\Property(property: 'code', type: 'string'),
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'credits', type: 'number', format: 'float'),
            ]),
        ]),
        new OA\Property(property: 'semester_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'semester', type: 'object', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'confirmed', 'completed', 'dropped', 'withdrawn']),
        new OA\Property(property: 'enrolled_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'dropped_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
class Enrollment {}
