<?php

namespace Tests\Feature\Auth;

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

    public function test_user_can_authenticate_and_is_redirected_to_users(): void
    {
        $user = User::factory()->superAdmin()->create(['email' => 'boss@test.test']);

        $this->post('/login', [
            'email' => 'boss@test.test',
            'password' => 'password',
        ])->assertRedirect(route('users.index'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role_id' => $user->role_id]);
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

    public function test_user_can_logout(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }
}
