<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** An assignment submission (no storage key or direct URL). */
#[OA\Schema(
    schema: 'Submission',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'assignment_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'enrollment_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'student', type: 'object', nullable: true),
        new OA\Property(property: 'submitted_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'status', type: 'string', enum: ['submitted', 'late', 'graded', 'returned']),
        new OA\Property(property: 'score', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'feedback', type: 'string', nullable: true),
        new OA\Property(property: 'graded_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'file', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'mime_type', type: 'string'),
            new OA\Property(property: 'size', type: 'integer'),
            new OA\Property(property: 'download_url', type: 'string', example: '/submissions/12/file'),
        ]),
    ]
)]
class Submission {}
