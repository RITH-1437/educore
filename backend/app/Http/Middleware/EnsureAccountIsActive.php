<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ends access for an account deactivated after it signed in (module 9.24 —
 * closes the "sessions / tokens stay valid until logout" open item).
 *
 * Runs in the `web` and `api` groups, before route middleware, and resolves
 * the user itself (session, or Sanctum token for API calls):
 * - web: the session is logged out and invalidated, redirect to sign-in;
 * - API: the presented token is deleted, 401 `Unauthenticated.`.
 * Both are written to the audit trail.
 */
class EnsureAccountIsActive
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user() ?? $request->user('sanctum');

        if ($user === null || $user->is_active) {
            return $next($request);
        }

        $this->audit->record('auth.access_revoked', $user, description: 'Account is inactive; '.($request->user('sanctum') && ! Auth::guard('web')->check() ? 'API token deleted.' : 'session ended.'), actor: $user);

        if (! Auth::guard('web')->check()) {
            $user->currentAccessToken()?->delete();

            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $request->expectsJson()
            ? response()->json(['message' => 'Unauthenticated.'], 401)
            : redirect()->route('login')->withErrors(['email' => 'Your account has been deactivated. Contact the university office.']);
    }
}
