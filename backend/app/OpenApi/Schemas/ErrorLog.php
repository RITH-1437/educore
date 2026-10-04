<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** ErrorLog representation returned by ErrorLogResource. */
#[OA\Schema(
    schema: 'ErrorLog',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(
            property: 'status_code',
            type: 'integer',
            example: 404,
            description: 'Only 404 and 5xx responses are ever recorded.'
        ),
        new OA\Property(property: 'is_server_error', type: 'boolean', example: false),
        new OA\Property(property: 'is_not_found', type: 'boolean', example: true),
        new OA\Property(property: 'method', type: 'string', example: 'GET'),
        new OA\Property(
            property: 'url',
            type: 'string',
            example: '/departments/999',
            description: 'Request path only. Query strings are dropped because they can carry secrets.'
        ),
        new OA\Property(property: 'route_name', type: 'string', nullable: true, example: 'departments.show'),
        new OA\Property(property: 'exception_class', type: 'string', nullable: true, example: 'Illuminate\\Database\\QueryException'),
        new OA\Property(property: 'message', type: 'string', nullable: true),
        new OA\Property(property: 'ip_address', type: 'string', nullable: true, example: '203.0.113.7'),
        new OA\Property(property: 'user_agent', type: 'string', nullable: true),
        new OA\Property(
            property: 'user',
            type: 'object',
            nullable: true,
            description: 'Present when the request was authenticated. Null for most 404s.',
            properties: [
                new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
                new OA\Property(property: 'name', type: 'string', example: 'Super Admin'),
                new OA\Property(property: 'email', type: 'string', example: 'admin@educore.kh'),
            ]
        ),
        new OA\Property(
            property: 'context',
            type: 'object',
            nullable: true,
            description: 'Small non-sensitive diagnostics: exception code and origin file/line.',
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
class ErrorLog {}
