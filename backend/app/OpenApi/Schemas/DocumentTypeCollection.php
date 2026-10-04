<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Paginated DocumentType collection. */
#[OA\Schema(
    schema: 'DocumentTypeCollection',
    type: 'object',
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/DocumentType')),
        new OA\Property(property: 'links', type: 'object', description: 'Laravel pagination links.'),
        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
    ]
)]
class DocumentTypeCollection {}
