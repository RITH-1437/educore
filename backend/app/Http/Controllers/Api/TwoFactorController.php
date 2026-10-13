<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Two-factor sign-in (report 52): the caller's own settings, and a Super
 * Admin's reset of another account. Sign-in itself takes `two_factor_code` /
 * `recovery_code` on `POST /login`.
 */
class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactor,
    ) {}

    #[OA\Get(
        path: '/two-factor',
        summary: "The caller's two-factor sign-in status",
        operationId: 'twoFactorStatus',
        tags: ['Auth'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Status; `setup` while a setup is pending.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/TwoFactorStatus'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->status($request->user())]);
    }

    #[OA\Post(
        path: '/two-factor',
        summary: 'Start two-factor setup',
        description: 'Creates a new secret (replacing an unfinished setup) and returns it with the `otpauth://` URI and an SVG QR code for the authenticator app.',
        operationId: 'startTwoFactor',
        tags: ['Auth'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Setup details.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/TwoFactorStatus'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Already on.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $this->twoFactor->start($request->user());

        return response()->json(['data' => $this->status($request->user())]);
    }

    #[OA\Post(
        path: '/two-factor/confirm',
        summary: 'Finish setup with a code from the authenticator app',
        description: 'Turns two-factor on and returns eight single-use recovery codes — shown only now.',
        operationId: 'confirmTwoFactor',
        tags: ['Auth'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['code'], properties: [new OA\Property(property: 'code', type: 'string', example: '123456')])),
        responses: [
            new OA\Response(response: 200, description: 'On; the recovery codes.', content: new OA\JsonContent(ref: '#/components/schemas/TwoFactorRecoveryCodes')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Already on, or no setup started.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Wrong or reused code.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function confirm(Request $request): JsonResponse
    {
        $code = $request->validate(['code' => ['required', 'string', 'max:20']])['code'];

        return response()->json(['data' => ['recovery_codes' => $this->twoFactor->confirm($request->user(), $code)]]);
    }

    #[OA\Post(
        path: '/two-factor/recovery-codes',
        summary: 'Replace the recovery codes',
        description: 'Needs the current password. The previous codes stop working.',
        operationId: 'regenerateTwoFactorRecoveryCodes',
        tags: ['Auth'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['current_password'], properties: [new OA\Property(property: 'current_password', type: 'string', format: 'password')])),
        responses: [
            new OA\Response(response: 200, description: 'The new codes.', content: new OA\JsonContent(ref: '#/components/schemas/TwoFactorRecoveryCodes')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Two-factor is not on.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Wrong password.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function regenerate(Request $request): JsonResponse
    {
        $password = $request->validate(['current_password' => ['required', 'string']])['current_password'];

        return response()->json(['data' => ['recovery_codes' => $this->twoFactor->regenerateRecoveryCodes($request->user(), $password)]]);
    }

    #[OA\Delete(
        path: '/two-factor',
        summary: 'Turn two-factor off (or cancel a setup)',
        description: 'Needs the current password.',
        operationId: 'disableTwoFactor',
        tags: ['Auth'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['current_password'], properties: [new OA\Property(property: 'current_password', type: 'string', format: 'password')])),
        responses: [
            new OA\Response(response: 204, description: 'Off.'),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Wrong password.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function destroy(Request $request): JsonResponse
    {
        $password = $request->validate(['current_password' => ['required', 'string']])['current_password'];
        $this->twoFactor->disable($request->user(), $password);

        return response()->json(null, 204);
    }

    #[OA\Delete(
        path: '/users/{user}/two-factor',
        summary: "Reset another account's two-factor sign-in (Super Admin)",
        description: 'For a user who lost both their phone and recovery codes. Not for your own account (use `DELETE /two-factor`). Audited; the owner is emailed.',
        operationId: 'resetUserTwoFactor',
        tags: ['Users'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'user', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 204, description: 'Reset.'),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin, or your own account.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'User not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Two-factor is not on for that account.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function reset(Request $request, User $user): JsonResponse
    {
        $this->authorize('resetTwoFactor', $user);
        $this->twoFactor->reset($user, $request->user());

        return response()->json(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function status(User $user): array
    {
        return [
            'enabled' => $user->hasTwoFactorEnabled(),
            'confirmed_at' => $user->two_factor_confirmed_at?->toIso8601String(),
            'recovery_codes_left' => $this->twoFactor->recoveryCodesLeft($user),
            'setup' => $this->twoFactor->setup($user),
        ];
    }
}
