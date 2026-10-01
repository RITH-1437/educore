<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Single CourseOfferingResource envelope. */
#[OA\Schema(
    schema: 'CourseOfferingResourceResponse',
    type: 'object',
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/CourseOffering')]
)]
class CourseOfferingResourceResponse {}
