<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\PasswordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Password change (signed in) and reset (guest) over the API.
 */
class PasswordController extends Controller
{
    public function __construct(
        private readonly PasswordService $passwords,
    ) {}

    #[OA\Put(
        path: '/password',
        summary: 'Change my password',
        description: 'Requires the current password; the new one needs at least 8 characters with letters and numbers and must differ. All other API tokens are deleted (the one making the request is kept); the owner is emailed.',
        operationId: 'changePassword',
        tags: ['Auth'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['current_password', 'password', 'password_confirmation'], properties: [
            new OA\Property(property: 'current_password', type: 'string', format: 'password'),
            new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8),
            new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Changed.', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Wrong current password or weak new password.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function update(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->passwords->change($user, $request->validated('current_password'), $request->validated('password'), $user->currentAccessToken()?->getKey());

        return response()->json(['message' => 'Password changed. Other sessions have been signed out.']);
    }

    #[OA\Post(
        path: '/forgot-password',
        summary: 'Request a password reset link',
        description: 'Public; rate limited. Always answers 202 with the same message, whether or not an active account uses the email (no account enumeration). The emailed link expires and works once.',
        operationId: 'forgotPassword',
        tags: ['Auth'],
        security: [],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['email'], properties: [new OA\Property(property: 'email', type: 'string', format: 'email')])),
        responses: [
            new OA\Response(response: 202, description: 'Accepted.', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 422, description: 'Invalid email.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 429, description: 'Too many requests.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function forgot(Request $request): JsonResponse
    {
        $this->passwords->sendResetLink($request->validate(['email' => ['required', 'string', 'email']])['email']);

        return response()->json(['message' => PasswordResetController::SENT], 202);
    }

    #[OA\Post(
        path: '/reset-password',
        summary: 'Reset a password with an emailed token',
        description: 'Public; rate limited. Invalid or expired token → 422. On success every API token of the account is deleted and the owner is emailed.',
        operationId: 'resetPassword',
        tags: ['Auth'],
        security: [],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['token', 'email', 'password', 'password_confirmation'], properties: [
            new OA\Property(property: 'token', type: 'string'),
            new OA\Property(property: 'email', type: 'string', format: 'email'),
            new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8),
            new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Reset.', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 422, description: 'Invalid / expired token or weak password.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 429, description: 'Too many requests.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $this->passwords->reset($request->validated());

        return response()->json(['message' => 'Password reset. Sign in with the new password.']);
    }
}
