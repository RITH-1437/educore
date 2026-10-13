<?php

namespace Tests\Feature\Auth;

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\TwoFactorChanged;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Report 52 — two-factor sign-in: setup, web and API sign-in with a code or a
 * recovery code, single use, limits, turning off, Super Admin reset.
 */
class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Carbon::setTestNow('2026-10-13 08:00:10');

        $this->user = User::factory()->create(['email' => 'dara@example.edu']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ------------------------------------------------------------------ setup

    public function test_setup_needs_a_code_and_shows_the_recovery_codes_once(): void
    {
        $this->actingAs($this->user)->get('/account/two-factor')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/TwoFactor')->where('enabled', false)->where('setup', null));

        $this->actingAs($this->user)->post('/account/two-factor')->assertSessionHas('success');
        $this->actingAs($this->user)->get('/account/two-factor')
            ->assertInertia(fn (Assert $page) => $page->where('enabled', false)
                ->where('setup.otpauth_url', fn (string $url) => str_starts_with($url, 'otpauth://totp/'))
                ->where('setup.qr_svg', fn (string $svg) => str_contains($svg, '<svg')));

        $secret = $this->user->fresh()->two_factor_secret;
        $this->actingAs($this->user)->post('/account/two-factor/confirm', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertFalse($this->user->fresh()->hasTwoFactorEnabled());

        $this->actingAs($this->user)->post('/account/two-factor/confirm', ['code' => $this->codeFor($secret)])->assertSessionHasNoErrors();
        $this->assertTrue($this->user->fresh()->hasTwoFactorEnabled());

        $codes = null;
        $this->actingAs($this->user)->get('/account/two-factor')
            ->assertInertia(function (Assert $page) use (&$codes) {
                $page->where('enabled', true)->where('setup', null)->where('recoveryCodesLeft', 8)->has('recoveryCodes', 8);
                $codes = $page->toArray()['props']['recoveryCodes'];
            });
        $this->assertMatchesRegularExpression('/^[a-z0-9]{5}-[a-z0-9]{5}$/', $codes[0]);

        // Shown once only.
        $this->actingAs($this->user)->get('/account/two-factor')->assertInertia(fn (Assert $page) => $page->where('recoveryCodes', null));

        // Stored encrypted, recovery codes only as hashes, never serialised.
        $row = DB::table('users')->where('id', $this->user->id)->first();
        $this->assertStringNotContainsString($secret, (string) $row->two_factor_secret);
        $this->assertStringNotContainsString($codes[0], decrypt($row->two_factor_recovery_codes, false));
        $this->actingAs($this->user)->getJson('/api/user')->assertJsonMissingPath('two_factor_secret')->assertJsonMissingPath('two_factor_recovery_codes');

        $this->assertTrue(AuditLog::query()->where('action', 'auth.two_factor_enabled')->where('actor_id', $this->user->id)->exists());
        Notification::assertSentTo($this->user, TwoFactorChanged::class, fn ($n) => $n->change === TwoFactorChanged::ENABLED);

        // Already on: no second setup.
        $this->actingAs($this->user)->postJson('/api/two-factor')->assertStatus(409);
    }

    // ---------------------------------------------------------------- web sign-in

    public function test_web_sign_in_asks_for_the_code_and_each_code_works_once(): void
    {
        $secret = $this->enable();

        $this->post('/login', ['email' => 'dara@example.edu', 'password' => 'password'])->assertRedirect('/two-factor-challenge');
        $this->assertGuest();
        $this->get('/two-factor-challenge')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Auth/TwoFactorChallenge'));

        $this->post('/two-factor-challenge', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();
        $this->assertTrue(AuditLog::query()->where('action', 'auth.two_factor_failed')->exists());

        $code = $this->codeFor($secret);
        $this->post('/two-factor-challenge', ['code' => $code])->assertRedirect(route('role-dashboard'));
        $this->assertAuthenticatedAs($this->user);
        $this->assertTrue(AuditLog::query()->where('action', 'auth.login')->where('actor_id', $this->user->id)->exists());

        // The same code again, a few seconds later: refused.
        $this->post('/logout');
        Carbon::setTestNow(now()->addSeconds(10));
        $this->post('/login', ['email' => 'dara@example.edu', 'password' => 'password']);
        $this->post('/two-factor-challenge', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();

        // The next code works.
        Carbon::setTestNow(now()->addSeconds(Totp::PERIOD));
        $this->post('/two-factor-challenge', ['code' => $this->codeFor($secret)])->assertRedirect();
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_a_recovery_code_signs_in_once(): void
    {
        $this->enable();
        $codes = $this->recoveryCodes();

        $this->post('/login', ['email' => 'dara@example.edu', 'password' => 'password']);
        $this->post('/two-factor-challenge', ['recovery_code' => strtoupper($codes[2])])->assertRedirect();
        $this->assertAuthenticatedAs($this->user);
        $this->assertSame(7, count($this->user->fresh()->two_factor_recovery_codes));
        $this->assertTrue(AuditLog::query()->where('action', 'auth.recovery_code_used')->exists());

        $this->post('/logout');
        $this->post('/login', ['email' => 'dara@example.edu', 'password' => 'password']);
        $this->post('/two-factor-challenge', ['recovery_code' => $codes[2]])->assertSessionHasErrors('recovery_code');
        $this->assertGuest();
    }

    public function test_the_code_page_expires_and_is_rate_limited(): void
    {
        $this->enable();

        // Nothing pending: back to the password form.
        $this->get('/two-factor-challenge')->assertRedirect('/login');

        $this->post('/login', ['email' => 'dara@example.edu', 'password' => 'password']);
        foreach (range(1, 5) as $i) {
            $this->post('/two-factor-challenge', ['code' => '000000'])->assertSessionHasErrors('code');
        }
        $this->post('/two-factor-challenge', ['code' => '000000'])
            ->assertSessionHasErrors(['code' => 'Too many codes. Try again in 60 seconds.']);

        // After five minutes the password is needed again.
        Carbon::setTestNow(now()->addMinutes(6));
        $this->post('/two-factor-challenge', ['code' => '123456'])->assertRedirect('/login');
        $this->assertGuest();
    }

    // ---------------------------------------------------------------- API sign-in

    public function test_api_sign_in_needs_the_code_in_the_same_request(): void
    {
        $secret = $this->enable();
        $codes = $this->recoveryCodes();
        $login = fn (array $extra = []) => $this->postJson('/api/login', ['email' => 'dara@example.edu', 'password' => 'password', ...$extra]);

        $login()->assertUnprocessable()->assertJsonValidationErrors('two_factor_code')->assertJsonMissingPath('data.token');
        $login(['two_factor_code' => '000000'])->assertUnprocessable()->assertJsonValidationErrors('two_factor_code');
        $login(['two_factor_code' => $this->codeFor($secret)])->assertOk()->assertJsonStructure(['data' => ['token']]);
        $login(['recovery_code' => $codes[0]])->assertOk()->assertJsonStructure(['data' => ['token']]);

        // A wrong password never reaches the second factor.
        $this->postJson('/api/login', ['email' => 'dara@example.edu', 'password' => 'nope', 'two_factor_code' => '000000'])->assertJsonValidationErrors('email');
    }

    // ------------------------------------------------------------- turning off

    public function test_turning_off_and_new_recovery_codes_need_the_password(): void
    {
        $this->enable();
        $old = $this->recoveryCodes();

        $this->actingAs($this->user)->postJson('/api/two-factor/recovery-codes', ['current_password' => 'nope'])->assertJsonValidationErrors('current_password');
        $new = $this->actingAs($this->user)->postJson('/api/two-factor/recovery-codes', ['current_password' => 'password'])
            ->assertOk()->assertJsonCount(8, 'data.recovery_codes')->json('data.recovery_codes');
        $this->assertNotEquals($old, $new);
        $this->actingAs($this->user)->getJson('/api/two-factor')->assertJsonPath('data.enabled', true)->assertJsonPath('data.recovery_codes_left', 8);

        $this->actingAs($this->user)->deleteJson('/api/two-factor', ['current_password' => 'nope'])->assertJsonValidationErrors('current_password');
        $this->assertTrue($this->user->fresh()->hasTwoFactorEnabled());

        $this->signedOut();
        $this->actingAs($this->user, 'web')->delete('/account/two-factor', ['current_password' => 'password'])->assertSessionHas('success', 'Two-factor sign-in is off.');
        $this->assertFalse($this->user->fresh()->hasTwoFactorEnabled());
        $this->assertNull($this->user->fresh()->two_factor_secret);
        $this->assertTrue(AuditLog::query()->where('action', 'auth.two_factor_disabled')->exists());
        Notification::assertSentTo($this->user, TwoFactorChanged::class, fn ($n) => $n->change === TwoFactorChanged::DISABLED);

        // Sign-in is back to the password alone.
        $this->post('/logout');
        $this->signedOut();
        $this->post('/login', ['email' => 'dara@example.edu', 'password' => 'password'])->assertRedirect(route('role-dashboard'));
    }

    public function test_a_super_admin_resets_another_accounts_two_factor(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $this->enable();

        $this->actingAs($this->user)->deleteJson("/api/users/{$this->user->id}/two-factor")->assertForbidden();
        $this->actingAs($admin)->deleteJson("/api/users/{$admin->id}/two-factor")->assertForbidden();

        $this->actingAs($admin)->get("/users/{$this->user->id}/edit")
            ->assertInertia(fn (Assert $page) => $page->where('user.two_factor_enabled', true));

        $this->actingAs($admin)->delete("/users/{$this->user->id}/two-factor")->assertSessionHas('success');
        $this->assertFalse($this->user->fresh()->hasTwoFactorEnabled());
        $this->assertTrue(AuditLog::query()->where('action', 'auth.two_factor_reset')->where('actor_id', $admin->id)->exists());
        Notification::assertSentTo($this->user, TwoFactorChanged::class, fn ($n) => $n->change === TwoFactorChanged::RESET);

        $this->actingAs($admin)->deleteJson("/api/users/{$this->user->id}/two-factor")->assertStatus(409);
    }

    // ---------------------------------------------------------------- helpers

    /** Turn two-factor on for the user through the API; returns the secret. */
    private function enable(): string
    {
        $this->actingAs($this->user)->postJson('/api/two-factor')->assertOk()->assertJsonPath('data.enabled', false);
        $secret = $this->user->fresh()->two_factor_secret;

        $this->recovery = $this->actingAs($this->user)->postJson('/api/two-factor/confirm', ['code' => $this->codeFor($secret)])
            ->assertOk()->json('data.recovery_codes');

        // Sign-in tests start signed out, a step later (the setup code is spent).
        $this->signedOut();
        Carbon::setTestNow(now()->addSeconds(Totp::PERIOD));

        return $secret;
    }

    /** @var list<string> */
    private array $recovery = [];

    /** API calls switch the default guard to Sanctum; web steps start from a clean slate. */
    private function signedOut(): void
    {
        config(['auth.defaults.guard' => 'web']);
        auth()->forgetGuards();
    }

    /** @return list<string> */
    private function recoveryCodes(): array
    {
        return $this->recovery;
    }

    private function codeFor(string $secret): string
    {
        return Totp::codeForStep($secret, Totp::stepAt(now()->getTimestamp()));
    }
}
