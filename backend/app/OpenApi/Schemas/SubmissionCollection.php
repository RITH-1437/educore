<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Submission list. */
#[OA\Schema(
    schema: 'SubmissionCollection',
    type: 'object',
    properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Submission'))]
)]
class SubmissionCollection {}
