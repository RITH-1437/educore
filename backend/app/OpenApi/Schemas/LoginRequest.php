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
            example: 'user@example.edu',
            description: 'E-mail address of the account.'
        ),
        new OA\Property(
            property: 'password',
            type: 'string',
            format: 'password',
            example: 'your-password',
            description: 'Account password.'
        ),
        new OA\Property(
            property: 'remember',
            type: 'boolean',
            description: 'Optional remember-me flag read by the shared login request.',
            example: false
        ),
    ]
)]
class LoginRequest {}
