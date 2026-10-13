<?php

namespace App\Services;

use App\Dto\Auth\LoginResult;
use App\Dto\User\UserData;
use App\Http\Requests\Auth\LoginRequest;
use App\Repositories\UserRepository;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Authentication business logic: credential verification, the second factor
 * (report 52), login bookkeeping, token issuing and revocation.
 */
class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly TwoFactorService $twoFactor,
    ) {}

    /**
     * Verify the request credentials — and, when two-factor is on, the
     * `two_factor_code` or `recovery_code` sent with them — and issue a fresh
     * API token. A missing code answers 422 on `two_factor_code` without
     * counting as a failure; a wrong one counts toward the sign-in limit.
     */
    public function login(LoginRequest $request): LoginResult
    {
        $user = $request->validateCredentials();

        if ($user->hasTwoFactorEnabled()) {
            $code = $request->input('two_factor_code');
            $recovery = $request->input('recovery_code');

            if (blank($code) && blank($recovery)) {
                throw ValidationException::withMessages(['two_factor_code' => 'Two-factor sign-in is on for this account: send the code from the authenticator app as two_factor_code, or a recovery code as recovery_code.']);
            }

            if (! $this->twoFactor->verify($user, $code, $recovery)) {
                $request->fail(filled($code) ? 'two_factor_code' : 'recovery_code', 'The code is not valid.');
            }
        }

        $request->clearAttempts();
        event(new Login('sanctum', $user, false));
        $this->users->recordLogin($user);

        return new LoginResult(
            token: $user->createToken('api')->plainTextToken,
            user: UserData::fromModel($user->fresh()->load('role'), withRole: true),
        );
    }

    /**
     * Revoke the token that authenticated the current request.
     */
    public function logout(Request $request): void
    {
        $request->user()?->currentAccessToken()?->delete();
    }
}
