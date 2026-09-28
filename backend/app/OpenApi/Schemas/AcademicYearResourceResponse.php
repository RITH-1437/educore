<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Single AcademicYearResource response envelope. */
#[OA\Schema(
    schema: 'AcademicYearResourceResponse',
    type: 'object',
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/AcademicYear')]
)]
class AcademicYearResourceResponse {}
