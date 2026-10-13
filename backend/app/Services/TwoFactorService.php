<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\User;
use App\Notifications\TwoFactorChanged;
use App\Support\QrCode;
use App\Support\Totp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Two-factor sign-in (report 52), optional for every role.
 *
 * - Setup: `start()` stores a fresh secret (unconfirmed); `confirm()` checks a
 *   code from the authenticator app, turns two-factor on and returns eight
 *   single-use recovery codes, shown once (only their SHA-256 hashes are kept).
 * - Sign-in: `verify()` accepts a current code — each one only once — or an
 *   unused recovery code, which is then spent.
 * - Turning it off or new recovery codes need the current password; a Super
 *   Admin can reset another account's two-factor (lost phone and codes).
 * - Every change is audited and emailed to the owner (critical notice).
 */
class TwoFactorService
{
    public const RECOVERY_CODES = 8;

    public function __construct(private readonly AuditLogger $audit) {}

    /** Begin setup with a new secret; any unfinished setup is replaced. */
    public function start(User $user): void
    {
        if ($user->hasTwoFactorEnabled()) {
            throw new BusinessRuleException('Two-factor sign-in is already on. Turn it off first to set up another authenticator.');
        }

        $user->forceFill(['two_factor_secret' => Totp::generateSecret(), 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
    }

    /**
     * What the authenticator app needs while setup is pending: the secret (for
     * typing in), the `otpauth://` URI and its QR code. Null otherwise.
     *
     * @return array{secret: string, otpauth_url: string, qr_svg: string}|null
     */
    public function setup(User $user): ?array
    {
        if ($user->hasTwoFactorEnabled() || blank($user->two_factor_secret)) {
            return null;
        }

        $uri = Totp::provisioningUri($user->two_factor_secret, $user->email, (string) config('app.name', 'EduCore'));

        return ['secret' => trim(chunk_split($user->two_factor_secret, 4, ' ')), 'otpauth_url' => $uri, 'qr_svg' => QrCode::svg($uri, 192, 0)];
    }

    /**
     * Finish setup with a code from the app. Returns the recovery codes.
     *
     * @return list<string>
     */
    public function confirm(User $user, string $code): array
    {
        if ($user->hasTwoFactorEnabled()) {
            throw new BusinessRuleException('Two-factor sign-in is already on.');
        }

        if (blank($user->two_factor_secret)) {
            throw new BusinessRuleException('Start the setup first.');
        }

        if (! $this->consumeCode($user, $code)) {
            throw ValidationException::withMessages(['code' => 'That code is not valid. Check that the time on your phone is set automatically and try the newest code.']);
        }

        return DB::transaction(function () use ($user) {
            $codes = $this->newRecoveryCodes();
            $user->forceFill(['two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => $this->hashes($codes)])->save();

            $this->audit->record('auth.two_factor_enabled', $user, description: 'Two-factor sign-in turned on.', actor: $user);
            $user->notify(new TwoFactorChanged(TwoFactorChanged::ENABLED));

            return $codes;
        });
    }

    /**
     * Replace the recovery codes (the old ones stop working).
     *
     * @return list<string>
     */
    public function regenerateRecoveryCodes(User $user, string $password): array
    {
        $this->assertPassword($user, $password);
        $this->assertEnabled($user);

        return DB::transaction(function () use ($user) {
            $codes = $this->newRecoveryCodes();
            $user->forceFill(['two_factor_recovery_codes' => $this->hashes($codes)])->save();

            $this->audit->record('auth.two_factor_recovery_codes_regenerated', $user, description: 'New two-factor recovery codes; the previous ones no longer work.', actor: $user);
            $user->notify(new TwoFactorChanged(TwoFactorChanged::NEW_RECOVERY_CODES));

            return $codes;
        });
    }

    /** Turn two-factor off (or cancel an unfinished setup); needs the current password. */
    public function disable(User $user, string $password): void
    {
        $this->assertPassword($user, $password);
        $wasEnabled = $user->hasTwoFactorEnabled();

        DB::transaction(function () use ($user, $wasEnabled) {
            $this->clear($user);

            if ($wasEnabled) {
                $this->audit->record('auth.two_factor_disabled', $user, description: 'Two-factor sign-in turned off.', actor: $user);
                $user->notify(new TwoFactorChanged(TwoFactorChanged::DISABLED));
            }
        });
    }

    /** A Super Admin turns off another account's two-factor (lost phone and recovery codes). */
    public function reset(User $user, User $by): void
    {
        $this->assertEnabled($user);

        DB::transaction(function () use ($user, $by) {
            $this->clear($user);
            $this->audit->record('auth.two_factor_reset', $user, description: "Two-factor sign-in reset by {$by->name}.", actor: $by);
            $user->notify(new TwoFactorChanged(TwoFactorChanged::RESET));
        });
    }

    /**
     * The second factor at sign-in: a current authenticator code (each code
     * works once) or an unused recovery code (spent on use). Failures are
     * audited.
     */
    public function verify(User $user, ?string $code, ?string $recoveryCode): bool
    {
        $valid = filled($code)
            ? $this->consumeCode($user, (string) $code)
            : (filled($recoveryCode) && $this->consumeRecoveryCode($user, (string) $recoveryCode));

        if (! $valid) {
            $this->audit->record('auth.two_factor_failed', $user, description: filled($code) ? 'Wrong two-factor code.' : 'Wrong recovery code.');
        }

        return $valid;
    }

    public function recoveryCodesLeft(User $user): int
    {
        return count($user->two_factor_recovery_codes ?? []);
    }

    /** A code matching now (± one step), not used before: replaying it is refused. */
    private function consumeCode(User $user, string $code): bool
    {
        $step = Totp::matchingStep((string) $user->two_factor_secret, $code, now()->getTimestamp());

        return $step !== null && Cache::add("two-factor-step:{$user->getKey()}:{$step}", true, now()->addSeconds(Totp::PERIOD * 4));
    }

    private function consumeRecoveryCode(User $user, string $code): bool
    {
        return DB::transaction(function () use ($user, $code) {
            // Locked: the same code cannot be spent twice at once.
            $locked = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $hash = hash('sha256', $this->normalize($code));
            $codes = $locked->two_factor_recovery_codes ?? [];
            $match = collect($codes)->search(fn (string $stored) => hash_equals($stored, $hash));

            if ($match === false) {
                return false;
            }

            unset($codes[$match]);
            $locked->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();
            $user->setRawAttributes($locked->getAttributes(), true);

            $this->audit->record('auth.recovery_code_used', $locked, description: 'Signed in with a recovery code; '.count($codes).' left.', actor: $locked);

            return true;
        });
    }

    private function clear(User $user): void
    {
        $user->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
    }

    /**
     * Eight codes like `k3f9a-x2m7q`.
     *
     * @return list<string>
     */
    private function newRecoveryCodes(): array
    {
        return collect(range(1, self::RECOVERY_CODES))
            ->map(fn () => implode('-', str_split(Str::lower(Str::random(10)), 5)))
            ->all();
    }

    /**
     * @param  list<string>  $codes
     * @return list<string>
     */
    private function hashes(array $codes): array
    {
        return array_map(fn (string $code) => hash('sha256', $this->normalize($code)), $codes);
    }

    private function normalize(string $code): string
    {
        return Str::lower((string) preg_replace('/[^A-Za-z0-9]/', '', $code));
    }

    private function assertPassword(User $user, string $password): void
    {
        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'The password is not correct.']);
        }
    }

    private function assertEnabled(User $user): void
    {
        if (! $user->hasTwoFactorEnabled()) {
            throw new BusinessRuleException('Two-factor sign-in is not on for this account.');
        }
    }
}
