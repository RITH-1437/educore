<?php

namespace App\Services;

use App\Dto\Auth\LoginResult;
use App\Dto\User\UserData;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Authentication business logic: credential verification, login bookkeeping,
 * token issuing and revocation.
 */
class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
    ) {}

    /**
     * Verify the request credentials and issue a fresh API token.
     */
    public function login(LoginRequest $request): LoginResult
    {
        $request->authenticate();

        /** @var User $user */
        $user = Auth::user();

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
