<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Room representation. */
#[OA\Schema(
    schema: 'Room',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'code', type: 'string', example: 'B-201'),
        new OA\Property(property: 'name', type: 'string', example: 'Lecture Hall 201'),
        new OA\Property(property: 'building', type: 'string', nullable: true),
        new OA\Property(property: 'floor', type: 'string', nullable: true),
        new OA\Property(property: 'capacity', type: 'integer', example: 60),
        new OA\Property(property: 'room_type', type: 'string', enum: ['lecture', 'lab', 'seminar', 'other']),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'schedule_entries_count', type: 'integer'),
    ]
)]
class Room {}
