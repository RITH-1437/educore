<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Single EnrollmentResource envelope. */
#[OA\Schema(
    schema: 'EnrollmentResourceResponse',
    type: 'object',
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Enrollment')]
)]
class EnrollmentResourceResponse {}
