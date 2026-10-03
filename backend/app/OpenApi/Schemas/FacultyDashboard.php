<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Faculty dashboard (`docs/34_Faculty-Admin-Dashboard-Report.md`). */
#[OA\Schema(
    schema: 'FacultyDashboard',
    type: 'object',
    properties: [
        new OA\Property(property: 'faculty', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'code', type: 'string'),
            new OA\Property(property: 'name', type: 'string'),
        ]),
        new OA\Property(property: 'semester', type: 'object', nullable: true, description: 'Current semester (as in analytics: the open one with the latest start, else the latest); null when none exists.', properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'name', type: 'string'),
        ]),
        new OA\Property(property: 'waiting', type: 'object', description: "Requests of the faculty's students waiting to be processed (point in time).", properties: [
            new OA\Property(property: 'document_requests_pending', type: 'integer', description: 'To approve or reject.'),
            new OA\Property(property: 'document_requests_approved', type: 'integer', description: 'Approved; the PDF is not generated yet.'),
            new OA\Property(property: 'internships_submitted', type: 'integer', description: 'Submitted; review not started.'),
            new OA\Property(property: 'internships_under_review', type: 'integer', description: 'Under review; waiting for approval or rejection.'),
        ]),
        new OA\Property(property: 'overview', type: 'object', properties: [
            new OA\Property(property: 'students_active', type: 'integer', description: 'Active students with a program in the faculty.'),
            new OA\Property(property: 'lecturers_active', type: 'integer', description: "Active lecturers of the faculty's departments."),
            new OA\Property(property: 'sections', type: 'integer', nullable: true, description: "Running sections of the faculty's courses this semester; null without a semester."),
            new OA\Property(property: 'students_enrolled', type: 'integer', nullable: true, description: "The faculty's students with an open or completed enrollment this semester; null without a semester."),
        ]),
    ]
)]
class FacultyDashboard {}
