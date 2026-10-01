<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Single schedule entry envelope. */
#[OA\Schema(
    schema: 'ScheduleEntryResourceResponse',
    type: 'object',
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/ScheduleEntry')]
)]
class ScheduleEntryResourceResponse {}
