<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** University Admin dashboard (`docs/36_University-Admin-Dashboard-Report.md`). */
#[OA\Schema(
    schema: 'UniversityDashboard',
    type: 'object',
    properties: [
        new OA\Property(property: 'semester', type: 'object', nullable: true, description: 'Current semester; null when none exists.', properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'name', type: 'string'),
        ]),
        new OA\Property(property: 'waiting', type: 'object', properties: [
            new OA\Property(property: 'document_requests_pending', type: 'integer', description: 'Pending document requests waiting for approval.'),
            new OA\Property(property: 'document_requests_approved', type: 'integer', description: 'Approved document requests waiting for PDF generation.'),
            new OA\Property(property: 'internships_submitted', type: 'integer', description: 'Submitted internship applications waiting for review.'),
            new OA\Property(property: 'internships_under_review', type: 'integer', description: 'Internship applications under review waiting for decision.'),
            new OA\Property(property: 'invoices_overdue', type: 'integer', description: 'Invoices that are past due date.'),
        ]),
        new OA\Property(property: 'overview', type: 'object', properties: [
            new OA\Property(property: 'students_active', type: 'integer'),
            new OA\Property(property: 'lecturers_active', type: 'integer'),
            new OA\Property(property: 'sections', type: 'integer', nullable: true),
            new OA\Property(property: 'enrollments', type: 'integer', nullable: true),
            new OA\Property(property: 'students_enrolled', type: 'integer', nullable: true),
            new OA\Property(property: 'attendance_rate', type: 'number', format: 'float', nullable: true),
            new OA\Property(property: 'grades_approved', type: 'integer', nullable: true),
            new OA\Property(property: 'pass_rate', type: 'number', format: 'float', nullable: true),
            new OA\Property(property: 'average_gpa', type: 'number', format: 'float', nullable: true),
        ]),
    ]
)]
class UniversityDashboard {}
