<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for updating a course (PUT and PATCH share the rules). */
#[OA\Schema(
    schema: 'UpdateCourseRequest',
    type: 'object',
    required: ['code', 'name', 'credits'],
    properties: [
        new OA\Property(property: 'department_id', type: 'integer', format: 'int64', example: 1, description: 'Optional; moves the course to another active department.'),
        new OA\Property(property: 'code', type: 'string', maxLength: 20, example: 'CS201'),
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Data Structures'),
        new OA\Property(property: 'credits', type: 'number', format: 'float', exclusiveMinimum: 0, maximum: 99.99, example: 3),
        new OA\Property(property: 'lecture_hours', type: 'integer', minimum: 0, nullable: true),
        new OA\Property(property: 'lab_hours', type: 'integer', minimum: 0, nullable: true),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'course_level', type: 'string', nullable: true, enum: ['introductory', 'intermediate', 'advanced', 'graduate']),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'active'], description: 'Ignored for archived courses; use reactivate.'),
    ]
)]
class UpdateCourseRequest {}
