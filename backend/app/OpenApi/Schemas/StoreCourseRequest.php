<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for creating a course. */
#[OA\Schema(
    schema: 'StoreCourseRequest',
    type: 'object',
    required: ['department_id', 'code', 'name', 'credits'],
    properties: [
        new OA\Property(property: 'department_id', type: 'integer', format: 'int64', example: 1, description: 'An active (non-archived) department.'),
        new OA\Property(property: 'code', type: 'string', maxLength: 20, example: 'CS201', description: 'Globally unique.'),
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Data Structures'),
        new OA\Property(property: 'credits', type: 'number', format: 'float', exclusiveMinimum: 0, maximum: 99.99, example: 3),
        new OA\Property(property: 'lecture_hours', type: 'integer', minimum: 0, nullable: true, example: 30),
        new OA\Property(property: 'lab_hours', type: 'integer', minimum: 0, nullable: true, example: 15),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'course_level', type: 'string', nullable: true, enum: ['introductory', 'intermediate', 'advanced', 'graduate']),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'active'], default: 'active', description: 'Archiving uses the archive endpoint.'),
    ]
)]
class StoreCourseRequest {}
