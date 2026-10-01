<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for a weekly meeting. */
#[OA\Schema(
    schema: 'ScheduleEntryRequest',
    type: 'object',
    required: ['room_id', 'day_of_week', 'start_time', 'end_time'],
    properties: [
        new OA\Property(property: 'room_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'day_of_week', type: 'integer', minimum: 1, maximum: 7),
        new OA\Property(property: 'start_time', type: 'string', pattern: '^\\d{2}:\\d{2}$', example: '08:00'),
        new OA\Property(property: 'end_time', type: 'string', pattern: '^\\d{2}:\\d{2}$', example: '09:30'),
    ]
)]
class ScheduleEntryRequest {}
