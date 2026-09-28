<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * OpenAPI definitions for the API routes that are declared as closures in
 * `routes/api.php` and therefore have no controller to attach attributes to.
 *
 * Documentation only: no runtime behaviour lives here.
 */
class ClosureRoutes
{
    #[OA\Get(
        path: '/user',
        summary: 'Fetch the authenticated user',
        description: 'Returns the user record attached to the current Sanctum token. Useful as an auth check after login. Unlike the user administration endpoints this payload is the raw user model (hiding `password` and `remember_token`).',
        operationId: 'getAuthenticatedUser',
        tags: ['Auth'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The authenticated user.',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthenticatedUser')
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function authenticatedUser(): void
    {
        //
    }

    #[OA\Get(
        path: '/health',
        summary: 'Service health check',
        description: 'Public endpoint used by uptime monitors and container health checks. Returns `ok` as soon as the application can serve requests.',
        operationId: 'health',
        tags: ['System'],
        security: [],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The service is up.',
                content: new OA\JsonContent(ref: '#/components/schemas/HealthResponse')
            ),
        ]
    )]
    public function health(): void
    {
        //
    }
}
