<?php

namespace Tests\Feature\Auth;

use App\Models\AuditLog;
use App\Models\Lecturer;
use App\Models\User;
use App\Notifications\PasswordChanged;
use App\Notifications\ResetPasswordLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Password change (signed in) and reset (guest): rules, token handling,
 * session / token revocation, no account enumeration, audit and notices.
 */
class PasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_change_password_over_the_api_keeps_only_the_current_token(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('this-device')->plainTextToken;
        $user->createToken('other-device');

        $this->withToken($current)->putJson('/api/password', ['current_password' => 'wrong', 'password' => 'NewPass123', 'password_confirmation' => 'NewPass123'])
            ->assertJsonValidationErrors('current_password');
        $this->withToken($current)->putJson('/api/password', ['current_password' => 'password', 'password' => 'short1', 'password_confirmation' => 'short1'])
            ->assertJsonValidationErrors('password');
        $this->withToken($current)->putJson('/api/password', ['current_password' => 'password', 'password' => 'lettersonly', 'password_confirmation' => 'lettersonly'])
            ->assertJsonValidationErrors('password');
        $this->withToken($current)->putJson('/api/password', ['current_password' => 'password', 'password' => 'password', 'password_confirmation' => 'password'])
            ->assertJsonValidationErrors('password');

        $this->withToken($current)->putJson('/api/password', ['current_password' => 'password', 'password' => 'NewPass123', 'password_confirmation' => 'NewPass123'])->assertOk();

        $this->assertTrue(Hash::check('NewPass123', $user->refresh()->password));
        $this->assertSame(['this-device'], $user->tokens()->pluck('name')->all());
        Notification::assertSentTo($user, PasswordChanged::class);
        $log = AuditLog::query()->where('action', 'user.password_changed')->firstOrFail();
        $this->assertStringNotContainsString('NewPass123', json_encode($log->toArray()));
    }

    public function test_change_password_on_the_web_ends_other_sessions_only(): void
    {
        $user = Lecturer::factory()->create()->user;

        $this->actingAs($user)->get('/account/password')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Account/Password'));
        $this->actingAs($user)->put('/account/password', ['current_password' => 'password', 'password' => 'NewPass123', 'password_confirmation' => 'NewPass123'])
            ->assertSessionHas('success');
        // The session that changed it stays signed in.
        $this->get('/dashboard')->assertOk();

        // Another session still carrying the old password hash is signed out.
        $this->app['auth']->forgetGuards();
        $this->actingAs($user)->withSession(['password_hash_web' => Hash::make('stale')])->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_reset_flow_is_single_use_and_does_not_enumerate_accounts(): void
    {
        $user = User::factory()->create(['email' => 'dara@example.com']);
        $user->createToken('old-device');
        $inactive = User::factory()->create(['email' => 'gone@example.com', 'is_active' => false]);

        // Same answer for a known, an unknown and an inactive email.
        foreach (['dara@example.com', 'nobody@example.com', 'gone@example.com'] as $email) {
            $this->postJson('/api/forgot-password', ['email' => $email])->assertStatus(202)->assertJsonPath('message', fn ($m) => str_starts_with($m, 'If an active account'));
        }
        Notification::assertSentTo($user, ResetPasswordLink::class);
        Notification::assertNotSentTo($inactive, ResetPasswordLink::class);

        $token = null;
        Notification::assertSentTo($user, ResetPasswordLink::class, function (ResetPasswordLink $n) use (&$token, $user) {
            $token = $n->token;

            return $n->via($user) === ['mail'] && str_contains($n->toMail($user)->actionUrl, "/reset-password/{$n->token}?email=dara%40example.com");
        });

        $this->postJson('/api/reset-password', ['token' => 'bogus', 'email' => 'dara@example.com', 'password' => 'Fresh12345', 'password_confirmation' => 'Fresh12345'])
            ->assertJsonValidationErrors('email');
        $this->postJson('/api/reset-password', ['token' => $token, 'email' => 'dara@example.com', 'password' => 'Fresh12345', 'password_confirmation' => 'Fresh12345'])->assertOk();

        $this->assertTrue(Hash::check('Fresh12345', $user->refresh()->password));
        $this->assertSame(0, $user->tokens()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.password_reset', 'actor_id' => $user->id]);
        Notification::assertSentTo($user, PasswordChanged::class);

        // Single use.
        $this->postJson('/api/reset-password', ['token' => $token, 'email' => 'dara@example.com', 'password' => 'Again12345', 'password_confirmation' => 'Again12345'])
            ->assertJsonValidationErrors('email');
    }

    public function test_web_reset_pages_and_rate_limit(): void
    {
        User::factory()->create(['email' => 'dara@example.com']);

        $this->get('/login')->assertInertia(fn (Assert $page) => $page->component('Auth/Login')->where('canResetPassword', true));
        $this->get('/forgot-password')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Auth/ForgotPassword'));
        $this->post('/forgot-password', ['email' => 'dara@example.com'])->assertSessionHas('status');
        $this->get('/reset-password/some-token?email=dara@example.com')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/ResetPassword')->where('token', 'some-token')->where('email', 'dara@example.com'));
        $this->post('/reset-password', ['token' => 'bad', 'email' => 'dara@example.com', 'password' => 'Fresh12345', 'password_confirmation' => 'Fresh12345'])->assertSessionHasErrors('email');

        foreach (range(1, 4) as $i) {
            $this->postJson('/api/forgot-password', ['email' => 'dara@example.com']);
        }
        $this->postJson('/api/forgot-password', ['email' => 'dara@example.com'])->assertStatus(429);
    }
}
