<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Successful `POST /api/login` response.
 */
#[OA\Schema(
    schema: 'LoginResponse',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'token',
                    type: 'string',
                    example: '3|Yb3m2Tq1Zc8L...',
                    description: 'Sanctum personal access token. Send as `Authorization: Bearer <token>`.'
                ),
                new OA\Property(property: 'user', ref: '#/components/schemas/User'),
            ]
        ),
    ]
)]
class LoginResponse {}
