<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Laravel validation error payload (`422`).
 */
#[OA\Schema(
    schema: 'ValidationErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string')
            ),
            example: ['email' => ['The email has already been taken.']],
            description: 'Field name mapped to the list of validation messages.'
        ),
    ]
)]
class ValidationErrorResponse {}
