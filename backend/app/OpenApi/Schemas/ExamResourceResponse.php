<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Single exam envelope. */
#[OA\Schema(
    schema: 'ExamResourceResponse',
    type: 'object',
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Exam')]
)]
class ExamResourceResponse {}
