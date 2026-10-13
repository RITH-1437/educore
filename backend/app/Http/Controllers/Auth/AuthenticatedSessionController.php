<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Web sign-in. With two-factor on (report 52) a correct password does not
 * sign in: the account waits in the session for five minutes while the code
 * page asks for an authenticator or recovery code.
 */
class AuthenticatedSessionController extends Controller
{
    /** How long a correct password waits for its second factor. */
    private const CHALLENGE_MINUTES = 5;

    public function __construct(
        private readonly TwoFactorService $twoFactor,
    ) {}

    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => true,
            'status' => session('status'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $request->validateCredentials();
        $request->clearAttempts();

        if ($user->hasTwoFactorEnabled()) {
            $request->session()->put('two_factor.login', [
                'id' => $user->getKey(),
                'remember' => $request->boolean('remember'),
                'expires' => now()->addMinutes(self::CHALLENGE_MINUTES)->getTimestamp(),
            ]);

            return redirect()->route('two-factor.challenge');
        }

        return $this->signIn($request, $user, $request->boolean('remember'));
    }

    /** The code page; without a pending sign-in it goes back to the password form. */
    public function challenge(Request $request): Response|RedirectResponse
    {
        if ($this->pendingUser($request) === null) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    }

    public function verifyChallenge(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['nullable', 'required_without:recovery_code', 'string', 'max:20'],
            'recovery_code' => ['nullable', 'string', 'max:40'],
        ]);
        $user = $this->pendingUser($request);

        if ($user === null) {
            return redirect()->route('login')->withErrors(['email' => 'Your sign-in expired. Enter your password again.']);
        }

        $key = "two-factor:{$user->getKey()}";
        $field = filled($data['code'] ?? null) ? 'code' : 'recovery_code';

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([$field => 'Too many codes. Try again in '.RateLimiter::availableIn($key).' seconds.']);
        }

        if (! $this->twoFactor->verify($user, $data['code'] ?? null, $data['recovery_code'] ?? null)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([$field => $field === 'code' ? 'That code is not valid. Try the newest code from your app.' : 'That recovery code is not valid or was already used.']);
        }

        RateLimiter::clear($key);
        $remember = (bool) $request->session()->pull('two_factor.login')['remember'];

        return $this->signIn($request, $user, $remember);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function signIn(Request $request, User $user, bool $remember): RedirectResponse
    {
        Auth::guard('web')->login($user, $remember);

        $user->forceFill(['last_login_at' => now()])->save();

        $request->session()->regenerate();

        if ($request->session()->has('url.intended')) {
            return redirect()->intended(route('home'));
        }

        return $user->isRole(Role::SuperAdmin->value)
            ? redirect()->route('admin.dashboard')
            : redirect()->route('role-dashboard');
    }

    /** The account waiting for its second factor, if still valid and active. */
    private function pendingUser(Request $request): ?User
    {
        $pending = $request->session()->get('two_factor.login');

        if (! is_array($pending) || ($pending['expires'] ?? 0) < now()->getTimestamp()) {
            $request->session()->forget('two_factor.login');

            return null;
        }

        $user = User::query()->where('is_active', true)->find($pending['id'] ?? null);

        return $user?->hasTwoFactorEnabled() ? $user : null;
    }
}
