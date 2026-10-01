<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Single LecturerResource response envelope. */
#[OA\Schema(
    schema: 'LecturerResourceResponse',
    type: 'object',
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Lecturer')]
)]
class LecturerResourceResponse {}
