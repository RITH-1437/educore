<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Login'));
    }

    public function test_super_admin_authenticates_and_is_redirected_to_admin_dashboard(): void
    {
        $user = User::factory()->superAdmin()->create(['email' => 'boss@test.test']);

        $this->post('/login', [
            'email' => 'boss@test.test',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role_id' => $user->role_id]);
    }

    public function test_university_admin_is_redirected_to_role_dashboard(): void
    {
        $role = Role::factory()->withSlug('university-admin')->create();
        $user = User::factory()->create([
            'email' => 'university-admin@test.test',
            'role_id' => $role->id,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('role-dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_student_account_without_profile_sees_an_empty_student_dashboard(): void
    {
        $role = Role::factory()->withSlug('student')->create();
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Dashboard')
                ->where('dashboard', null)
                ->where('userName', $user->name)
                ->missing('areas'));
    }

    public function test_non_super_admin_cannot_view_admin_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_super_admin_can_view_admin_dashboard_with_management_summary(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->has('stats.total_users')
                ->has('stats.total_academic_years')
                ->has('roleCounts')
                ->has('recentUsers'));
    }

    public function test_wrong_credentials_are_rejected_with_a_generic_message(): void
    {
        User::factory()->create(['email' => 'who@test.test']);

        $this->post('/login', [
            'email' => 'who@test.test',
            'password' => 'not-the-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_unknown_email_does_not_reveal_existence(): void
    {
        $this->post('/login', [
            'email' => 'ghost@test.test',
            'password' => 'whatever123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_account_cannot_sign_in_and_gets_the_generic_message(): void
    {
        User::factory()->create(['email' => 'off@test.test', 'is_active' => false]);

        $wrong = $this->post('/login', ['email' => 'off@test.test', 'password' => 'not-the-password'])
            ->assertSessionHasErrors('email');
        $wrongMessage = session('errors')->first('email');

        $this->post('/login', ['email' => 'off@test.test', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => $wrongMessage]);

        $this->assertGuest();
    }

    public function test_inactive_account_cannot_obtain_an_api_token(): void
    {
        User::factory()->create(['email' => 'off-api@test.test', 'is_active' => false]);

        $this->postJson('/api/login', ['email' => 'off-api@test.test', 'password' => 'password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email'])
            ->assertJsonMissingPath('token');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_reactivated_account_can_sign_in_again(): void
    {
        $user = User::factory()->create(['email' => 'back@test.test', 'is_active' => false]);
        $user->update(['is_active' => true]);

        $this->post('/login', ['email' => 'back@test.test', 'password' => 'password'])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $user = User::factory()->create(['email' => 'slow@test.test']);
        RateLimiter::clear('slow@test.test|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'slow@test.test',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $this->post('/login', [
            'email' => 'slow@test.test',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_super_admin_is_redirected_to_dashboard_from_root(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('admin.dashboard'));
    }

    /**
     * Audit SEC-01 (`docs/audits/security-findings.md`): API tokens never
     * expired, so a leaked token kept working until someone signed out.
     */
    public function test_api_tokens_expire_after_the_configured_lifetime(): void
    {
        User::factory()->create(['email' => 'token@test.test']);
        $token = $this->postJson('/api/login', ['email' => 'token@test.test', 'password' => 'password'])->assertOk()->json('data.token');

        $this->assertSame(10080, config('sanctum.expiration'));
        $this->withToken($token)->getJson('/api/user')->assertOk();

        $this->travel(10079)->minutes();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/user')->assertOk();

        $this->travel(2)->minutes();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
    }

    /**
     * Audit SEC-02: signing out of the API revoked the token but left no
     * audit entry (only the web sign-out fired the Logout event).
     */
    public function test_api_logout_revokes_the_token_and_is_audited(): void
    {
        $user = User::factory()->create(['email' => 'api-out@test.test']);
        $token = $this->postJson('/api/login', ['email' => 'api-out@test.test', 'password' => 'password'])->assertOk()->json('data.token');

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.logout', 'actor_id' => $user->id]);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }
}
