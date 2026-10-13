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
        new OA\Property(
            property: 'two_factor_code',
            type: 'string',
            nullable: true,
            example: '123456',
            description: 'Required when two-factor sign-in is on (or send `recovery_code`): the current 6-digit code of the authenticator app. Each code works once (report 52).'
        ),
        new OA\Property(
            property: 'recovery_code',
            type: 'string',
            nullable: true,
            example: 'k3f9a-x2m7q',
            description: 'Instead of `two_factor_code`: an unused recovery code; it is spent.'
        ),
    ]
)]
class LoginRequest {}
