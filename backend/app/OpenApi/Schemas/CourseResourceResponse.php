<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Single CourseResource response envelope. */
#[OA\Schema(
    schema: 'CourseResourceResponse',
    type: 'object',
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Course')]
)]
class CourseResourceResponse {}
