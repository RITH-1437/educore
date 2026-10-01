<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for a program transfer. */
#[OA\Schema(
    schema: 'ChangeStudentProgramRequest',
    type: 'object',
    required: ['program_id'],
    properties: [
        new OA\Property(property: 'program_id', type: 'integer', format: 'int64', description: 'An active program other than the current one.'),
        new OA\Property(property: 'effective_on', type: 'string', format: 'date', nullable: true, description: 'Transfer date; default today; not before the current period started.'),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
    ]
)]
class ChangeStudentProgramRequest {}
