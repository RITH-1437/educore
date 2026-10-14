<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Public course catalog (report 53). */
#[OA\Schema(
    schema: 'CatalogDepartment',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'code', type: 'string', example: 'GIC'),
        new OA\Property(property: 'name', type: 'string', example: 'Department of Information and Communication Engineering'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'courses_count', type: 'integer', description: 'Active courses.'),
    ]
)]
#[OA\Schema(
    schema: 'CatalogSemester',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'name', type: 'string', example: '2026-2027 Semester 1'),
        new OA\Property(property: 'status', type: 'string', example: 'open'),
    ]
)]
#[OA\Schema(
    schema: 'CatalogCourse',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'code', type: 'string', example: 'CS101'),
        new OA\Property(property: 'name', type: 'string', example: 'Introduction to Programming'),
        new OA\Property(property: 'credits', type: 'number', example: 3),
        new OA\Property(property: 'course_level', type: 'string', nullable: true),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'department', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'code', type: 'string'),
            new OA\Property(property: 'name', type: 'string'),
        ]),
        new OA\Property(property: 'offered', type: 'boolean', description: 'Taught this semester.'),
        new OA\Property(property: 'lecturers', type: 'array', items: new OA\Items(type: 'string', example: 'Dr. Sokha Chan'), description: 'This semester; empty when the catalog hides lecturers.'),
    ]
)]
#[OA\Schema(
    schema: 'CatalogCourseDetail',
    type: 'object',
    allOf: [new OA\Schema(ref: '#/components/schemas/CatalogCourse')],
    properties: [
        new OA\Property(property: 'lecture_hours', type: 'number', nullable: true),
        new OA\Property(property: 'lab_hours', type: 'number', nullable: true),
        new OA\Property(property: 'prerequisites', type: 'array', items: new OA\Items(properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'code', type: 'string'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'is_strict', type: 'boolean'),
        ])),
        new OA\Property(property: 'programs', type: 'array', items: new OA\Items(properties: [
            new OA\Property(property: 'code', type: 'string'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'is_required', type: 'boolean'),
        ])),
        new OA\Property(property: 'sections', type: 'array', description: 'This semester.', items: new OA\Items(properties: [
            new OA\Property(property: 'code', type: 'string', example: 'A'),
            new OA\Property(property: 'lecturers', type: 'array', items: new OA\Items(type: 'string')),
            new OA\Property(property: 'meetings', type: 'array', items: new OA\Items(properties: [
                new OA\Property(property: 'day', type: 'string', example: 'Monday'),
                new OA\Property(property: 'start_time', type: 'string', example: '08:00'),
                new OA\Property(property: 'end_time', type: 'string', example: '09:30'),
                new OA\Property(property: 'room', type: 'string', nullable: true, example: 'F-201'),
            ])),
        ])),
    ]
)]
class Catalog {}
