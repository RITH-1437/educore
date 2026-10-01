<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** A roster row with the student's result for an exam. */
#[OA\Schema(
    schema: 'ExamResultRow',
    type: 'object',
    properties: [
        new OA\Property(property: 'enrollment_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'student', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'student_number', type: 'string'),
            new OA\Property(property: 'full_name', type: 'string'),
        ]),
        new OA\Property(property: 'result_id', type: 'integer', format: 'int64', nullable: true),
        new OA\Property(property: 'score', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'remarks', type: 'string', nullable: true),
    ]
)]
class ExamResultRow {}
