<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for a status change. */
#[OA\Schema(
    schema: 'ChangeStudentStatusRequest',
    type: 'object',
    required: ['status'],
    properties: [
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive', 'suspended', 'graduated', 'withdrawn']),
        new OA\Property(property: 'effective_on', type: 'string', format: 'date', nullable: true, description: 'Closing date of the program period on graduation/withdrawal; default today.'),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
    ]
)]
class ChangeStudentStatusRequest {}
