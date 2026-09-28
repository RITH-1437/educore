<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for advancing a semester status. */
#[OA\Schema(
    schema: 'ChangeSemesterStatusRequest',
    type: 'object',
    required: ['status'],
    properties: [new OA\Property(property: 'status', type: 'string', enum: ['planned', 'open', 'closed', 'completed'], example: 'open')]
)]
class ChangeSemesterStatusRequest {}
