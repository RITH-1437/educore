<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
    ) {}

    #[OA\Post(
        path: '/login',
        summary: 'Authenticate and receive an API token',
        description: 'Verifies the credentials, stamps `last_login_at`, and returns a new Sanctum personal access token. Throttled to 5 attempts per minute per e-mail address and IP address.',
        operationId: 'login',
        tags: ['Auth'],
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/LoginRequest',
                example: [
                    'email' => 'admin@educore.kh',
                    'password' => 'admin@123',
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authentication succeeded.',
                content: new OA\JsonContent(ref: '#/components/schemas/LoginResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed or credentials are invalid.',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
            new OA\Response(
                response: 429,
                description: 'Too many login attempts. Retry after the rate limit window.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->auth->login($request);

        return response()->json([
            'data' => [
                'token' => $result->token,
                'user' => new UserResource($result->user),
            ],
        ]);
    }

    #[OA\Post(
        path: '/logout',
        summary: 'Revoke the current API token',
        operationId: 'logout',
        tags: ['Auth'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token revoked.',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request);

        return response()->json(['message' => 'Logged out.']);
    }
}
