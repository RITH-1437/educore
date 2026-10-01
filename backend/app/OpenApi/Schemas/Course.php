<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Course representation returned by CourseResource. */
#[OA\Schema(
    schema: 'Course',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'department_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(
            property: 'department',
            type: 'object',
            nullable: true,
            description: 'Present when the relation is loaded.',
            properties: [
                new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
                new OA\Property(property: 'code', type: 'string', example: 'CSE'),
                new OA\Property(property: 'name', type: 'string', example: 'Department of Computer Science and Engineering'),
                new OA\Property(property: 'faculty_id', type: 'integer', format: 'int64', example: 1),
                new OA\Property(
                    property: 'faculty',
                    type: 'object',
                    nullable: true,
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
                        new OA\Property(property: 'code', type: 'string', example: 'ENG'),
                        new OA\Property(property: 'name', type: 'string', example: 'Faculty of Engineering'),
                    ]
                ),
            ]
        ),
        new OA\Property(property: 'code', type: 'string', example: 'CS201'),
        new OA\Property(property: 'name', type: 'string', example: 'Data Structures'),
        new OA\Property(property: 'credits', type: 'number', format: 'float', example: 3.0),
        new OA\Property(property: 'lecture_hours', type: 'integer', nullable: true, example: 30),
        new OA\Property(property: 'lab_hours', type: 'integer', nullable: true, example: 15),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'course_level', type: 'string', nullable: true, enum: ['introductory', 'intermediate', 'advanced', 'graduate']),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'active', 'archived'], example: 'active'),
        new OA\Property(property: 'prerequisites_count', type: 'integer', description: 'Present on list responses.'),
        new OA\Property(property: 'programs_count', type: 'integer', description: 'Present on list responses.'),
        new OA\Property(
            property: 'prerequisites',
            type: 'array',
            description: 'Present when the relation is loaded.',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'integer', format: 'int64'),
                    new OA\Property(property: 'code', type: 'string', example: 'CS101'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'credits', type: 'number', format: 'float'),
                    new OA\Property(property: 'status', type: 'string'),
                    new OA\Property(property: 'is_strict', type: 'boolean'),
                ]
            )
        ),
        new OA\Property(
            property: 'programs',
            type: 'array',
            description: 'Programs whose curriculum contains the course. Present when the relation is loaded.',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'integer', format: 'int64'),
                    new OA\Property(property: 'code', type: 'string', example: 'BSCS'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'is_required', type: 'boolean'),
                    new OA\Property(property: 'suggested_semester', type: 'integer', nullable: true),
                ]
            )
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class Course {}
