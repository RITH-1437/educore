<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\SessionGuard;
use Illuminate\Session\Middleware\AuthenticateSession;

/**
 * Laravel's `AuthenticateSession`, applied only when the active guard is the
 * session guard: it signs out sessions whose stored password hash no longer
 * matches (a password change or reset elsewhere). The stock middleware calls
 * session-only methods on whatever the default guard is, and fails when that
 * guard is Sanctum's token guard.
 */
class AuthenticateWebSession extends AuthenticateSession
{
    public function handle($request, Closure $next)
    {
        if (! $this->auth->guard() instanceof SessionGuard) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
