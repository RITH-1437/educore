<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Single SemesterResource response envelope. */
#[OA\Schema(
    schema: 'SemesterResourceResponse',
    type: 'object',
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Semester')]
)]
class SemesterResourceResponse {}
