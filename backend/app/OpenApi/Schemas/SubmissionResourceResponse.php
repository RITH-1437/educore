<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Single submission envelope. */
#[OA\Schema(
    schema: 'SubmissionResourceResponse',
    type: 'object',
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Submission')]
)]
class SubmissionResourceResponse {}
