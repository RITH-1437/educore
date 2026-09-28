<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Root OpenAPI definition for the EduCore API.
 *
 * This class intentionally contains no logic: it only carries the global
 * OpenAPI attributes (document metadata, server list, tags) so that
 * swagger-php can pick them up while scanning the `app` directory.
 */
#[OA\Info(
    version: '1.0.0',
    title: 'EduCore API',
    description: <<<'TXT'
        REST API for the EduCore university digital administration platform.

        ### Authentication
        Endpoints are protected with Laravel Sanctum bearer tokens. Call
        `POST /api/login` with valid credentials to receive a token, then send
        it as an `Authorization: Bearer <token>` header on every protected
        request.

        ### Authorization
        User management endpoints (`/api/users`) require the `super-admin`
        role. Academic calendar endpoints (`/api/academic-years` and nested
        semesters) require `super-admin` or `university-admin`. Each endpoint
        documents its applicable authentication and role requirements.

        ### Rate limiting
        `POST /api/login` is throttled to 5 attempts per minute per
        e-mail address and IP address. Exceeding the limit returns
        `429 Too Many Requests`.

        ### Conventions
        * All request and response bodies are JSON.
        * Paginated list endpoints return `data` plus a `meta` object; non-paginated child collections return `data`.
        * Validation failures return `422` with a field-keyed `errors` object.
        TXT
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: 'EduCore API server (APP_URL + /api)'
)]
#[OA\Tag(
    name: 'System',
    description: 'Public operational endpoints.'
)]
#[OA\Tag(
    name: 'Auth',
    description: 'Token based authentication (Sanctum).'
)]
#[OA\Tag(
    name: 'Users',
    description: 'User administration. Requires the `super-admin` role.'
)]
class OpenApi {}
