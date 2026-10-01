<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Exam list. */
#[OA\Schema(
    schema: 'ExamCollection',
    type: 'object',
    properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Exam'))]
)]
class ExamCollection {}
