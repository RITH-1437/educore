<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for creating a document type. */
#[OA\Schema(
    schema: 'StoreDocumentTypeRequest',
    type: 'object',
    required: ['name', 'code'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 100, example: 'Dean Recommendation Letter'),
        new OA\Property(property: 'code', type: 'string', maxLength: 50, example: 'dean_recommendation'),
        new OA\Property(property: 'description', type: 'string', maxLength: 1000, nullable: true, example: 'Official recommendation letter signed by the dean.'),
        new OA\Property(property: 'requires_fee', type: 'boolean', example: true),
        new OA\Property(property: 'fee_amount', type: 'number', format: 'float', example: 10.00),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'sort_order', type: 'integer', example: 5),
    ]
)]
class StoreDocumentTypeRequest {}
