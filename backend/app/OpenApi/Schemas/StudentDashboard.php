<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Student academic dashboard (module 9.15). */
#[OA\Schema(
    schema: 'StudentDashboard',
    type: 'object',
    properties: [
        new OA\Property(property: 'student', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'student_number', type: 'string'),
            new OA\Property(property: 'full_name', type: 'string'),
            new OA\Property(property: 'status', type: 'string'),
            new OA\Property(property: 'program', type: 'object', nullable: true, properties: [
                new OA\Property(property: 'code', type: 'string'),
                new OA\Property(property: 'name', type: 'string'),
            ]),
        ]),
        new OA\Property(property: 'semester', type: 'object', nullable: true, description: 'Semester of the latest open enrollments.', properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'start_date', type: 'string', format: 'date', nullable: true),
            new OA\Property(property: 'end_date', type: 'string', format: 'date', nullable: true),
        ]),
        new OA\Property(property: 'gpa', type: 'object', properties: [
            new OA\Property(property: 'cumulative', type: 'number', nullable: true),
            new OA\Property(property: 'latest_semester', type: 'object', nullable: true, properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'gpa', type: 'number'),
            ]),
        ]),
        new OA\Property(property: 'credits', type: 'object', properties: [
            new OA\Property(property: 'current', type: 'number', description: 'Credits of open enrollments this semester.'),
            new OA\Property(property: 'courses', type: 'integer'),
            new OA\Property(property: 'earned', type: 'number'),
            new OA\Property(property: 'attempted', type: 'number'),
        ]),
        new OA\Property(property: 'attendance', type: 'object', properties: [
            new OA\Property(property: 'rate', type: 'number', nullable: true, description: '(present + late) / counted sessions this semester, in %.'),
            new OA\Property(property: 'courses', type: 'array', items: new OA\Items(properties: [
                new OA\Property(property: 'course', type: 'object', properties: [
                    new OA\Property(property: 'code', type: 'string'),
                    new OA\Property(property: 'name', type: 'string'),
                ]),
                new OA\Property(property: 'section', type: 'string'),
                new OA\Property(property: 'rate', type: 'number', nullable: true),
            ])),
        ]),
        new OA\Property(property: 'today', type: 'array', description: "Today's class meetings (timetable rows).", items: new OA\Items(type: 'object')),
        new OA\Property(property: 'assignments', type: 'array', description: 'Up to 5 published, unsubmitted assignments due soonest.', items: new OA\Items(properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'title', type: 'string'),
            new OA\Property(property: 'due_at', type: 'string', format: 'date-time'),
            new OA\Property(property: 'max_score', type: 'number'),
            new OA\Property(property: 'section_id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'course', type: 'object', properties: [
                new OA\Property(property: 'code', type: 'string'),
                new OA\Property(property: 'name', type: 'string'),
            ]),
        ])),
        new OA\Property(property: 'exams', type: 'array', description: 'Up to 5 upcoming exams (as in `/students/{student}/exams`).', items: new OA\Items(type: 'object')),
        new OA\Property(property: 'grades', type: 'array', description: 'Up to 5 most recent approved grades (as in `/students/{student}/grades`).', items: new OA\Items(type: 'object')),
    ]
)]
class StudentDashboard {}
