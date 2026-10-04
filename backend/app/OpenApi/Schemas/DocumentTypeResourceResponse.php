<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Single DocumentType resource response. */
#[OA\Schema(
    schema: 'DocumentTypeResourceResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/DocumentType'),
    ]
)]
class DocumentTypeResourceResponse {}
