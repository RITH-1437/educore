<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Session and roster for one date. */
#[OA\Schema(
    schema: 'AttendanceRosterResponse',
    type: 'object',
    properties: [new OA\Property(property: 'data', type: 'object', properties: [
        new OA\Property(property: 'section_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'date', type: 'string', format: 'date'),
        new OA\Property(property: 'session', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'status', type: 'string', enum: ['scheduled', 'held', 'cancelled']),
            new OA\Property(property: 'topic', type: 'string', nullable: true),
            new OA\Property(property: 'start_time', type: 'string', nullable: true),
            new OA\Property(property: 'end_time', type: 'string', nullable: true),
        ]),
        new OA\Property(property: 'roster', type: 'array', items: new OA\Items(type: 'object', properties: [
            new OA\Property(property: 'enrollment_id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'student', type: 'object'),
            new OA\Property(property: 'status', type: 'string', nullable: true, enum: ['present', 'absent', 'late', 'excused']),
            new OA\Property(property: 'remarks', type: 'string', nullable: true),
        ])),
    ])]
)]
class AttendanceRosterResponse {}
