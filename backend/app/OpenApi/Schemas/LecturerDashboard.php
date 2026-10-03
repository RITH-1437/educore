<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Lecturer dashboard (`docs/35_Lecturer-Dashboard-Report.md`). */
#[OA\Schema(
    schema: 'LecturerDashboard',
    type: 'object',
    properties: [
        new OA\Property(property: 'lecturer', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'staff_number', type: 'string'),
            new OA\Property(property: 'full_name', type: 'string'),
        ]),
        new OA\Property(property: 'semester', type: 'object', nullable: true, description: 'Current semester (as in analytics: the open one with the latest start, else the latest); null when none exists.', properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'name', type: 'string'),
        ]),
        new OA\Property(property: 'counts', type: 'object', properties: [
            new OA\Property(property: 'classes_today', type: 'integer'),
            new OA\Property(property: 'registers_to_take', type: 'integer', description: 'Past scheduled meetings this semester with no attendance session recorded.'),
            new OA\Property(property: 'submissions_to_grade', type: 'integer', description: 'Submissions still submitted or late (not graded or returned).'),
            new OA\Property(property: 'upcoming_exams', type: 'integer', description: 'Exams scheduled from today on.'),
        ]),
        new OA\Property(property: 'today', type: 'array', description: "Today's meetings (timetable rows), when today is inside the semester.", items: new OA\Items(type: 'object')),
        new OA\Property(property: 'exams', type: 'array', description: 'Up to 5 upcoming exams, soonest first (exam fields plus course, section_code, semester).', items: new OA\Items(type: 'object')),
        new OA\Property(property: 'sections', type: 'array', description: "The lecturer's sections in the semester.", items: new OA\Items(properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'code', type: 'string'),
            new OA\Property(property: 'course', type: 'object', properties: [
                new OA\Property(property: 'code', type: 'string'),
                new OA\Property(property: 'name', type: 'string'),
            ]),
            new OA\Property(property: 'role', type: 'string', enum: ['primary', 'assistant', 'tutor']),
            new OA\Property(property: 'students', type: 'integer', description: 'Pending or confirmed enrollments.'),
            new OA\Property(property: 'registers_to_take', type: 'integer'),
            new OA\Property(property: 'submissions_to_grade', type: 'integer'),
            new OA\Property(property: 'grades', type: 'string', enum: ['not_started', 'draft', 'submitted', 'approved', 'finalized', 'no_students']),
        ])),
    ]
)]
class LecturerDashboard {}
