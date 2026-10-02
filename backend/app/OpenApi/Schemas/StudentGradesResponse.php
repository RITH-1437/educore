<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StudentGradesResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'course', type: 'object', properties: [
                new OA\Property(property: 'code', type: 'string'),
                new OA\Property(property: 'name', type: 'string'),
            ]),
            new OA\Property(property: 'credits', type: 'number'),
            new OA\Property(property: 'section', type: 'string'),
            new OA\Property(property: 'semester_id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'semester', type: 'string'),
            new OA\Property(property: 'total_score', type: 'number', nullable: true),
            new OA\Property(property: 'letter_grade', type: 'string', nullable: true),
            new OA\Property(property: 'grade_point', type: 'number', nullable: true),
            new OA\Property(property: 'remarks', type: 'string', nullable: true),
            new OA\Property(property: 'approved_at', type: 'string', format: 'date-time', nullable: true),
        ])),
        new OA\Property(property: 'gpa', ref: '#/components/schemas/GpaSummary'),
    ]
)]
class StudentGradesResponse {}
