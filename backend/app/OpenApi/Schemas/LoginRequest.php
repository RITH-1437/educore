<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Body of `POST /api/login`.
 */
#[OA\Schema(
    schema: 'LoginRequest',
    type: 'object',
    required: ['email', 'password'],
    properties: [
        new OA\Property(
            property: 'email',
            type: 'string',
            format: 'email',
            example: 'admin@educore.kh',
            description: 'E-mail address of the account.'
        ),
        new OA\Property(
            property: 'password',
            type: 'string',
            format: 'password',
            example: 'admin@123',
            description: 'Account password.'
        ),
    ]
)]
class LoginRequest {}
