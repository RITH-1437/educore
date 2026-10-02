<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\PasswordChanged;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Password change and reset (`skills/authentication` — password security).
 *
 * - Change: the current password must match; the new one differs and follows
 *   `Password::defaults()` (≥ 8, letters and numbers). Every API token except
 *   the one making the request is deleted; other web sessions end through
 *   `AuthenticateSession` (their stored password hash no longer matches).
 * - Reset: Laravel's broker — a single-use token stored hashed, expiring after
 *   `auth.passwords.users.expire` minutes, throttled per user. Links go only
 *   to **active** accounts, and the response never reveals whether an email
 *   exists. A reset deletes every API token.
 * - Both are audited (`user.password_changed`, `auth.password_reset`) and the
 *   owner is told by email (critical notification).
 */
class PasswordService
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    /** @param int|null $keepTokenId the API token making the request (kept) */
    public function change(User $user, string $current, string $new, ?int $keepTokenId = null): void
    {
        if (! Hash::check($current, $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'The current password is incorrect.']);
        }

        DB::transaction(function () use ($user, $new, $keepTokenId) {
            $user->forceFill(['password' => $new, 'remember_token' => Str::random(60)])->save();
            $user->tokens()->when($keepTokenId, fn ($q) => $q->whereKeyNot($keepTokenId))->delete();
            $this->audit->record('user.password_changed', $user, description: 'Password changed by the account owner; other sessions and API tokens ended.', actor: $user);
            $user->notify(new PasswordChanged('changed'));
        });
    }

    /** Always succeeds from the caller's point of view (no account enumeration). */
    public function sendResetLink(string $email): void
    {
        // `is_active` is part of the lookup, so deactivated accounts never get a link.
        $status = Password::broker()->sendResetLink(['email' => $email, 'is_active' => true]);

        $this->audit->record('auth.password_reset_requested', null, description: "Reset link requested for {$email} ({$status}).");
    }

    /**
     * @param  array{email: string, token: string, password: string}  $data
     */
    public function reset(array $data): void
    {
        $status = Password::broker()->reset(
            ['email' => $data['email'], 'token' => $data['token'], 'password' => $data['password'], 'is_active' => true],
            function (User $user, string $password) {
                DB::transaction(function () use ($user, $password) {
                    $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                    $user->tokens()->delete();
                    $this->audit->record('auth.password_reset', $user, description: 'Password reset through an emailed link; all API tokens ended.', actor: $user);
                    $user->notify(new PasswordChanged('reset'));
                });
                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'This password reset link is invalid or has expired. Request a new one.']);
        }
    }
}
