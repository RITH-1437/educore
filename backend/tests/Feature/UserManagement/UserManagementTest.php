<?php

namespace Tests\Feature\UserManagement;

use App\Dto\User\UpdateUserData;
use App\Enums\Role;
use App\Exceptions\BusinessRuleException;
use App\Models\Lecturer;
use App\Models\Role as RoleModel;
use App\Models\Student;
use App\Models\User;
use App\Services\UserService;
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

    public function test_super_admin_can_view_the_user_edit_screen(): void
    {
        // `setUp()` already created a Student role, so reuse that user instead of
        // trying to insert a duplicate role row.
        // Guards against the single-resource nesting trap: a bare
        // `JsonResource` prop is serialised under `data`, and the Vue page reads
        // `props.user.name`.
        $this->actingAs($this->admin)
            ->get("/users/{$this->student->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Edit')
                ->where('user.id', $this->student->id)
                ->where('user.email', $this->student->email)
                ->has('roles'));
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

    public function test_unauthenticated_api_request_with_wildcard_accept_returns_json(): void
    {
        $this->get('/api/users', ['Accept' => '*/*'])
            ->assertUnauthorized()
            ->assertHeader('content-type', 'application/json')
            ->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public function test_user_list_caps_per_page(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/users?per_page=500')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100);
    }

    public function test_api_delete_soft_deletes_user_and_revokes_tokens(): void
    {
        $adminToken = $this->postJson('/api/login', [
            'email' => $this->admin->email,
            'password' => 'password',
        ])->json('data.token');

        $this->student->createToken('api');

        $this->deleteJson("/api/users/{$this->student->id}", [], ['Authorization' => "Bearer {$adminToken}"])
            ->assertNoContent();

        $this->assertSoftDeleted('users', ['id' => $this->student->id]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->student->id,
            'tokenable_type' => User::class,
        ]);
    }

    public function test_revoked_token_can_no_longer_authenticate(): void
    {
        $token = $this->student->createToken('api')->plainTextToken;

        app(UserService::class)->delete($this->student);

        $this->getJson('/api/user', ['Authorization' => "Bearer {$token}"])
            ->assertUnauthorized();
    }

    public function test_super_admin_can_filter_users_by_role_slug(): void
    {
        $this->actingAs($this->admin)
            ->get('/users?role=student')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Index')
                ->has('users.data', 1)
                ->where('users.data.0.id', $this->student->id)
                ->where('filters.role', 'student'));
    }

    public function test_super_admin_cannot_remove_their_own_access(): void
    {
        // Another active Super Admin exists: this is the self-lockout rule, not the last-admin one.
        User::factory()->create(['role_id' => $this->admin->role_id]);
        $self = fn (array $changes) => [...['name' => $this->admin->name, 'email' => $this->admin->email, 'role_id' => $this->admin->role_id], ...$changes];

        $this->actingAs($this->admin)
            ->put("/users/{$this->admin->id}", $self(['role_id' => $this->student->role_id]))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'your own Super Admin role'));

        $this->actingAs($this->admin)
            ->putJson("/api/users/{$this->admin->id}", $self(['is_active' => false]))
            ->assertStatus(409);

        $this->assertDatabaseHas('users', ['id' => $this->admin->id, 'role_id' => $this->admin->role_id, 'is_active' => true]);

        // Everything else about their own account still changes.
        $this->actingAs($this->admin)
            ->putJson("/api/users/{$this->admin->id}", $self(['name' => 'Root Admin', 'is_active' => true]))
            ->assertOk()
            ->assertJsonPath('data.name', 'Root Admin');
    }

    public function test_the_last_active_super_admin_is_never_removed(): void
    {
        $other = User::factory()->create(['role_id' => $this->admin->role_id]);

        // While another remains, a Super Admin may demote a colleague.
        $this->actingAs($other)
            ->putJson("/api/users/{$this->admin->id}", ['name' => $this->admin->name, 'email' => $this->admin->email, 'role_id' => $this->student->role_id])
            ->assertOk();

        // `$other` is now the last one: no path (here the service itself) removes them.
        $users = app(UserService::class);
        $demote = new UpdateUserData(name: $other->name, email: $other->email, roleId: $this->student->role_id);
        $deactivate = new UpdateUserData(name: $other->name, email: $other->email, roleId: $other->role_id, isActive: false);

        foreach ([fn () => $users->update($other, $demote), fn () => $users->update($other, $deactivate), fn () => $users->delete($other)] as $attempt) {
            try {
                $attempt();
                $this->fail('The last active Super Admin was removed.');
            } catch (BusinessRuleException $e) {
                $this->assertStringContainsString('last active Super Admin', $e->getMessage());
            }
        }

        $this->assertDatabaseHas('users', ['id' => $other->id, 'role_id' => $other->role_id, 'is_active' => true, 'deleted_at' => null]);
    }

    public function test_an_account_holding_a_profile_keeps_its_role_and_is_not_deleted(): void
    {
        $student = Student::factory()->create();
        $account = $student->user;
        $payload = ['name' => $account->name, 'email' => $account->email, 'role_id' => $account->role_id];

        $this->actingAs($this->admin)
            ->putJson("/api/users/{$account->id}", [...$payload, 'role_id' => $this->admin->role_id])
            ->assertStatus(409)
            ->assertJsonPath('message', fn (string $message) => str_contains($message, $student->student_number));

        $this->actingAs($this->admin)
            ->putJson("/api/users/{$account->id}", [...$payload, 'name' => 'Renamed'])
            ->assertOk();

        $this->actingAs($this->admin)->deleteJson("/api/users/{$account->id}")->assertStatus(409);
        $this->assertNotSoftDeleted('users', ['id' => $account->id]);
        $this->assertDatabaseHas('users', ['id' => $account->id, 'role_id' => $account->role_id]);

        $lecturer = Lecturer::factory()->create();
        $this->actingAs($this->admin)
            ->delete("/users/{$lecturer->user_id}")
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'Lecturers page'));
        $this->assertNotSoftDeleted('users', ['id' => $lecturer->user_id]);
    }
}
