<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** A personal weekly timetable. */
#[OA\Schema(
    schema: 'TimetableResponse',
    type: 'object',
    properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object', properties: [
        new OA\Property(property: 'day_of_week', type: 'integer'),
        new OA\Property(property: 'day', type: 'string'),
        new OA\Property(property: 'start_time', type: 'string'),
        new OA\Property(property: 'end_time', type: 'string'),
        new OA\Property(property: 'room', type: 'object'),
        new OA\Property(property: 'section', type: 'object'),
        new OA\Property(property: 'course', type: 'object'),
        new OA\Property(property: 'semester', type: 'string'),
        new OA\Property(property: 'lecturer', type: 'string', nullable: true),
    ]))]
)]
class TimetableResponse {}
