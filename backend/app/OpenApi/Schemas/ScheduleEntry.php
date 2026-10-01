<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** A weekly meeting of a section. */
#[OA\Schema(
    schema: 'ScheduleEntry',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'section_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'day_of_week', type: 'integer', minimum: 1, maximum: 7, description: '1 = Monday … 7 = Sunday.'),
        new OA\Property(property: 'day', type: 'string', example: 'Monday'),
        new OA\Property(property: 'start_time', type: 'string', example: '08:00'),
        new OA\Property(property: 'end_time', type: 'string', example: '09:30'),
        new OA\Property(property: 'room', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'code', type: 'string'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'capacity', type: 'integer'),
        ]),
    ]
)]
class ScheduleEntry {}
