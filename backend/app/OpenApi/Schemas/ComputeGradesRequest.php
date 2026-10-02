<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ComputeGradesRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'remarks', type: 'array', items: new OA\Items(properties: [
            new OA\Property(property: 'enrollment_id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'remarks', type: 'string', maxLength: 500, nullable: true),
        ])),
    ]
)]
class ComputeGradesRequest {}
