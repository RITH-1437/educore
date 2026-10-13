<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            // API sign-in with two-factor on (report 52); the web form asks for
            // the code on a second page instead.
            'two_factor_code' => ['nullable', 'string', 'max:20'],
            'recovery_code' => ['nullable', 'string', 'max:40'],
        ];
    }

    /**
     * The account these credentials belong to, without signing in — the caller
     * signs in once any second factor has passed (report 52).
     *
     * Inactive accounts are treated exactly like wrong credentials: same
     * generic message, `Failed` fired (audited), and the attempt counts toward
     * the rate limit, so the response never reveals that the account exists
     * (`skills/authentication/SKILL.md`). The limit is cleared by
     * `clearAttempts()` once sign-in completes.
     */
    public function validateCredentials(): User
    {
        $this->ensureIsNotRateLimited();

        $credentials = [...$this->only('email', 'password'), 'is_active' => true];
        $provider = Auth::guard('web')->getProvider();
        $user = $provider->retrieveByCredentials($credentials);

        if (! $user instanceof User || ! $provider->validateCredentials($user, $credentials)) {
            event(new Failed('web', $user, $credentials));

            $this->fail('email', __('These credentials do not match our records.'));
        }

        $provider->rehashPasswordIfRequired($user, $credentials);

        return $user;
    }

    /** A wrong second factor counts toward the same limit as a wrong password. */
    public function fail(string $field, string $message): never
    {
        RateLimiter::hit($this->throttleKey(), 60);

        throw ValidationException::withMessages([$field => $message]);
    }

    public function clearAttempts(): void
    {
        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
