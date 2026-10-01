<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Bulk attendance for one date. */
#[OA\Schema(
    schema: 'RecordAttendanceRequest',
    type: 'object',
    required: ['session_date', 'records'],
    properties: [
        new OA\Property(property: 'session_date', type: 'string', format: 'date', example: '2026-03-02'),
        new OA\Property(property: 'topic', type: 'string', nullable: true),
        new OA\Property(property: 'records', type: 'array', items: new OA\Items(type: 'object', required: ['enrollment_id', 'status'], properties: [
            new OA\Property(property: 'enrollment_id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'status', type: 'string', enum: ['present', 'absent', 'late', 'excused']),
            new OA\Property(property: 'remarks', type: 'string', nullable: true),
        ])),
    ]
)]
class RecordAttendanceRequest {}
