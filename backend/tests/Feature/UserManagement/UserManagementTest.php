<?php

namespace Tests\Feature\UserManagement;

use App\Enums\Role;
use App\Models\Role as RoleModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->superAdmin()->create(['email' => 'admin@test.test']);
        $this->student = User::factory()->create([
            'role_id' => RoleModel::factory()->withSlug(Role::Student->value)->create()->id,
        ]);
    }

    public function test_student_is_forbidden_from_users_index(): void
    {
        $this->actingAs($this->student)
            ->get('/users')
            ->assertForbidden();
    }

    public function test_student_is_forbidden_from_users_on_api(): void
    {
        $this->actingAs($this->student)
            ->getJson('/api/users')
            ->assertForbidden();
    }

    public function test_unauthenticated_api_request_is_rejected(): void
    {
        $this->getJson('/api/users')->assertUnauthorized();
    }

    public function test_super_admin_can_view_users_index(): void
    {
        $this->actingAs($this->admin)
            ->get('/users')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Index')
                ->has('users.data', 2));
    }

    public function test_super_admin_can_create_a_user(): void
    {
        $this->actingAs($this->admin)
            ->post('/users', [
                'name' => 'New Lecturer',
                'email' => 'lecturer.new@test.test',
                'role_id' => RoleModel::factory()->create(['slug' => Role::Lecturer->value])->id,
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
            ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['email' => 'lecturer.new@test.test', 'is_active' => true]);
    }

    public function test_create_user_validates_password_and_email_uniqueness(): void
    {
        $this->actingAs($this->admin)
            ->post('/users', [
                'name' => 'Short Password',
                'email' => 'short@test.test',
                'role_id' => $this->admin->role_id,
                'password' => 'short',
                'password_confirmation' => 'short',
            ])->assertSessionHasErrors('password');

        $this->actingAs($this->admin)
            ->post('/users', [
                'name' => 'Duplicate',
                'email' => $this->admin->email,
                'role_id' => $this->admin->role_id,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])->assertSessionHasErrors('email');
    }

    public function test_super_admin_can_update_a_user(): void
    {
        $this->actingAs($this->admin)
            ->put("/users/{$this->student->id}", [
                'name' => 'Renamed Student',
                'email' => $this->student->email,
                'role_id' => $this->student->role_id,
                'is_active' => false,
            ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $this->student->id,
            'name' => 'Renamed Student',
            'is_active' => false,
        ]);
    }

    public function test_super_admin_cannot_delete_themselves(): void
    {
        $this->actingAs($this->admin)
            ->delete("/users/{$this->admin->id}")
            ->assertForbidden();

        $this->assertNotSoftDeleted('users', ['id' => $this->admin->id]);
    }

    public function test_super_admin_can_soft_delete_another_user(): void
    {
        $this->actingAs($this->admin)
            ->delete("/users/{$this->student->id}")
            ->assertRedirect(route('users.index'));

        $this->assertSoftDeleted('users', ['id' => $this->student->id]);
    }

    public function test_api_login_returns_a_sanctum_token(): void
    {
        $password = 'password';

        $response = $this->postJson('/api/login', [
            'email' => $this->admin->email,
            'password' => $password,
        ])->assertOk();

        $response->assertJsonStructure([
            'data' => ['token', 'user' => ['id', 'name', 'email', 'role']],
        ]);
    }

    public function test_api_token_can_access_user_list(): void
    {
        $token = $this->postJson('/api/login', [
            'email' => $this->admin->email,
            'password' => 'password',
        ])->json('data.token');

        $this->getJson('/api/users', ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'name', 'email', 'role']],
                'meta' => ['current_page', 'per_page', 'total'],
            ]);
    }
}
