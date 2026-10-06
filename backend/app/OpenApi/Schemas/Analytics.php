<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Analytics responses (module 9.23). `semester` / `data` are null when no
 * semester exists. `department` names the department the figures are limited
 * to (report 47) — null for the whole university, and for a Department Admin
 * with no department (every figure is then empty).
 */
#[OA\Schema(
    schema: 'AnalyticsDepartment',
    type: 'object',
    nullable: true,
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'code', type: 'string'),
    ]
)]
#[OA\Schema(
    schema: 'AnalyticsSemester',
    type: 'object',
    nullable: true,
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'name', type: 'string'),
    ]
)]
#[OA\Schema(
    schema: 'AnalyticsOverviewResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'semester', ref: '#/components/schemas/AnalyticsSemester'),
        new OA\Property(property: 'department', ref: '#/components/schemas/AnalyticsDepartment'),
        new OA\Property(property: 'data', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'students_active', type: 'integer'),
            new OA\Property(property: 'lecturers_active', type: 'integer'),
            new OA\Property(property: 'sections', type: 'integer'),
            new OA\Property(property: 'enrollments', type: 'integer'),
            new OA\Property(property: 'students_enrolled', type: 'integer'),
            new OA\Property(property: 'attendance_rate', type: 'number', nullable: true, description: '% (present + late) / counted, excused excluded.'),
            new OA\Property(property: 'grades_approved', type: 'integer'),
            new OA\Property(property: 'pass_rate', type: 'number', nullable: true),
            new OA\Property(property: 'average_gpa', type: 'number', nullable: true, description: 'Mean semester GPA of students with a GPA snapshot.'),
        ]),
    ]
)]
#[OA\Schema(
    schema: 'AnalyticsEnrollmentResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'semester', ref: '#/components/schemas/AnalyticsSemester'),
        new OA\Property(property: 'department', ref: '#/components/schemas/AnalyticsDepartment'),
        new OA\Property(property: 'data', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'by_program', type: 'array', items: new OA\Items(properties: [
                new OA\Property(property: 'code', type: 'string'),
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'enrollments', type: 'integer'),
                new OA\Property(property: 'students', type: 'integer'),
            ])),
            new OA\Property(property: 'by_status', type: 'array', items: new OA\Items(properties: [
                new OA\Property(property: 'status', type: 'string'),
                new OA\Property(property: 'total', type: 'integer'),
            ])),
        ]),
    ]
)]
#[OA\Schema(
    schema: 'AnalyticsAcademicResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'semester', ref: '#/components/schemas/AnalyticsSemester'),
        new OA\Property(property: 'department', ref: '#/components/schemas/AnalyticsDepartment'),
        new OA\Property(property: 'data', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'grade_distribution', type: 'array', items: new OA\Items(properties: [
                new OA\Property(property: 'grade', type: 'string'),
                new OA\Property(property: 'total', type: 'integer'),
                new OA\Property(property: 'is_pass', type: 'boolean'),
            ])),
            new OA\Property(property: 'gpa_distribution', type: 'array', items: new OA\Items(properties: [
                new OA\Property(property: 'band', type: 'string'),
                new OA\Property(property: 'total', type: 'integer'),
            ])),
            new OA\Property(property: 'attendance_by_course', type: 'array', items: new OA\Items(properties: [
                new OA\Property(property: 'code', type: 'string'),
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'rate', type: 'number'),
            ])),
            new OA\Property(property: 'courses', type: 'array', items: new OA\Items(properties: [
                new OA\Property(property: 'code', type: 'string'),
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'graded', type: 'integer'),
                new OA\Property(property: 'pass_rate', type: 'number', nullable: true),
                new OA\Property(property: 'average_total', type: 'number', nullable: true),
                new OA\Property(property: 'average_point', type: 'number', nullable: true),
            ])),
        ]),
    ]
)]
#[OA\Schema(
    schema: 'AnalyticsStatusRow',
    type: 'object',
    properties: [
        new OA\Property(property: 'status', type: 'string'),
        new OA\Property(property: 'total', type: 'integer'),
    ]
)]
#[OA\Schema(
    schema: 'AnalyticsAdministrativeResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'department', ref: '#/components/schemas/AnalyticsDepartment'),
        new OA\Property(property: 'data', type: 'object', properties: [
            new OA\Property(property: 'documents', type: 'array', items: new OA\Items(ref: '#/components/schemas/AnalyticsStatusRow')),
            new OA\Property(property: 'internships', type: 'array', items: new OA\Items(ref: '#/components/schemas/AnalyticsStatusRow')),
            new OA\Property(property: 'invoices', type: 'array', nullable: true, description: 'Null for a department (finance is university-wide).', items: new OA\Items(ref: '#/components/schemas/AnalyticsStatusRow')),
            new OA\Property(property: 'finance', type: 'array', nullable: true, description: 'One row per currency. Null for a department.', items: new OA\Items(properties: [
                new OA\Property(property: 'currency', type: 'string'),
                new OA\Property(property: 'invoiced', type: 'number'),
                new OA\Property(property: 'collected', type: 'number'),
                new OA\Property(property: 'outstanding', type: 'number'),
                new OA\Property(property: 'overdue', type: 'number'),
                new OA\Property(property: 'overdue_count', type: 'integer'),
                new OA\Property(property: 'collection_rate', type: 'number', nullable: true),
            ])),
        ]),
    ]
)]
#[OA\Schema(
    schema: 'AnalyticsTrendsResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'department', ref: '#/components/schemas/AnalyticsDepartment'),
        new OA\Property(property: 'data', type: 'array', description: 'The latest six semesters, oldest first.', items: new OA\Items(properties: [
            new OA\Property(property: 'semester_id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'semester', type: 'string'),
            new OA\Property(property: 'status', type: 'string'),
            new OA\Property(property: 'enrollments', type: 'integer'),
            new OA\Property(property: 'students_enrolled', type: 'integer'),
            new OA\Property(property: 'attendance_rate', type: 'number', nullable: true),
            new OA\Property(property: 'pass_rate', type: 'number', nullable: true),
            new OA\Property(property: 'average_gpa', type: 'number', nullable: true),
        ])),
    ]
)]
class Analytics {}
