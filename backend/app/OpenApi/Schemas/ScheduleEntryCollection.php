<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Schedule entries list. */
#[OA\Schema(
    schema: 'ScheduleEntryCollection',
    type: 'object',
    properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ScheduleEntry'))]
)]
class ScheduleEntryCollection {}
