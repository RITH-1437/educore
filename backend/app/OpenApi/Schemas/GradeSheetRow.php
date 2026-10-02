<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** One student on a section's grade sheet. */
#[OA\Schema(
    schema: 'GradeSheetRow',
    type: 'object',
    properties: [
        new OA\Property(property: 'enrollment_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'student', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'student_number', type: 'string'),
            new OA\Property(property: 'full_name', type: 'string'),
        ]),
        new OA\Property(property: 'components', type: 'object', description: 'Component % (null = nothing to measure).', additionalProperties: new OA\AdditionalProperties(type: 'number', nullable: true)),
        new OA\Property(property: 'computed', type: 'object', properties: [
            new OA\Property(property: 'total', type: 'number', nullable: true),
            new OA\Property(property: 'letter', type: 'string', nullable: true),
            new OA\Property(property: 'grade_point', type: 'number', nullable: true),
        ]),
        new OA\Property(property: 'grade', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'status', type: 'string', enum: ['draft', 'submitted', 'approved', 'finalized']),
            new OA\Property(property: 'total_score', type: 'number', nullable: true),
            new OA\Property(property: 'letter_grade', type: 'string', nullable: true),
            new OA\Property(property: 'grade_point', type: 'number', nullable: true),
            new OA\Property(property: 'remarks', type: 'string', nullable: true),
            new OA\Property(property: 'submitted_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'approved_at', type: 'string', format: 'date-time', nullable: true),
        ]),
    ]
)]
class GradeSheetRow {}
