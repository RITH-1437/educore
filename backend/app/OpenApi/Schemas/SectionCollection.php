<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** SectionResource list (a lecturer's teaching load). */
#[OA\Schema(
    schema: 'SectionCollection',
    type: 'object',
    properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Section'))]
)]
class SectionCollection {}
