<?php

namespace App\Dto\Auth;

use App\Dto\User\UserData;

/**
 * Result of a successful authentication: the token plus the signed-in user.
 */
final readonly class LoginResult
{
    public function __construct(
        public string $token,
        public UserData $user,
    ) {}
}
