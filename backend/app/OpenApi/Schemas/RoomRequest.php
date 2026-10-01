<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for creating/updating a room. */
#[OA\Schema(
    schema: 'RoomRequest',
    type: 'object',
    required: ['code', 'name', 'capacity', 'room_type'],
    properties: [
        new OA\Property(property: 'code', type: 'string', maxLength: 50, description: 'Unique.'),
        new OA\Property(property: 'name', type: 'string', maxLength: 100),
        new OA\Property(property: 'building', type: 'string', nullable: true),
        new OA\Property(property: 'floor', type: 'string', nullable: true),
        new OA\Property(property: 'capacity', type: 'integer', minimum: 1),
        new OA\Property(property: 'room_type', type: 'string', enum: ['lecture', 'lab', 'seminar', 'other']),
        new OA\Property(property: 'is_active', type: 'boolean'),
    ]
)]
class RoomRequest {}
