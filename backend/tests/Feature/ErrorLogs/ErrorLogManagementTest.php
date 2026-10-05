<?php

namespace Tests\Feature\ErrorLogs;

use App\Enums\Role;
use App\Models\ErrorLog;
use App\Models\Role as RoleModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * System Error Logs — Super Admin read-only diagnostics.
 *
 * What is tested:
 *  - Only 404 and >= 500 responses are recorded.
 *  - 401/403/409/422 are NOT recorded (they are normal control flow).
 *  - Authorization: Super Admin only on web and API.
 *  - Search, filter (status code, group, method), sort, pagination.
 *  - Path-only storage — query string never persisted.
 *  - No mutation endpoints (append-only, like audit_logs).
 *  - Seeder is idempotent and produces realistic examples.
 */
class ErrorLogManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $universityAdmin;

    private User $departmentAdmin;

    private User $lecturer;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'root@test.test']);
        $this->universityAdmin = $this->userWithRole(Role::UniversityAdmin->value, 'dean@test.test');
        $this->departmentAdmin = $this->userWithRole(Role::DepartmentAdmin->value, 'unit@test.test');
        $this->lecturer = $this->userWithRole(Role::Lecturer->value, 'teacher@test.test');
        $this->student = $this->userWithRole(Role::Student->value, 'pupil@test.test');

        // Seed realistic examples (ErrorLogSeeder runs in DatabaseSeeder).
        $this->artisan('db:seed', ['--class' => 'ErrorLogSeeder']);
    }

    private function userWithRole(string $roleSlug, string $email): User
    {
        return User::factory()->create([
            'email' => $email,
            'role_id' => RoleModel::factory()->withSlug($roleSlug)->create()->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // Recording rules — the core invariant
    // -------------------------------------------------------------------------

    public function test_404_response_is_recorded(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/this-path-does-not-exist-'.uniqid())
            ->assertNotFound();

        $this->assertDatabaseHas('error_logs', ['status_code' => 404]);
    }

    public function test_500_response_is_recorded(): void
    {
        $url = '/test-500-'.uniqid();
        Route::get($url, fn () => abort(500, 'boom'));

        $this->actingAs($this->superAdmin)
            ->get($url)
            ->assertServerError();

        $this->assertDatabaseHas('error_logs', ['status_code' => 500]);
    }

    /** Requests clients make by themselves (DevTools, a desktop webview's IPC) are not EduCore failures. */
    public function test_client_probe_404s_are_not_recorded(): void
    {
        $before = ErrorLog::query()->count();

        $this->get('/.well-known/appspecific/com.chrome.devtools.json')->assertNotFound();
        $this->post('/plugin%3Awindow%7Cclose')->assertNotFound();
        $this->assertSame($before, ErrorLog::query()->count());

        // A real broken link is still recorded, and so is a 5xx on a probe path.
        $this->get('/.well-known/security.txt')->assertNotFound();
        $this->assertDatabaseHas('error_logs', ['status_code' => 404, 'url' => '/.well-known/security.txt']);
        Route::get('/.well-known/appspecific/boom', fn () => abort(500));
        $this->get('/.well-known/appspecific/boom')->assertServerError();
        $this->assertDatabaseHas('error_logs', ['status_code' => 500, 'url' => '/.well-known/appspecific/boom']);
    }

    /** The faculty pages were removed (report 39); old bookmarks move to departments instead of 404ing. */
    public function test_old_faculty_links_redirect_to_departments(): void
    {
        $before = ErrorLog::query()->count();

        $this->get('/faculties')->assertStatus(301)->assertRedirect('/departments');
        $this->get('/faculties/3/edit')->assertStatus(301)->assertRedirect('/departments');
        $this->assertSame($before, ErrorLog::query()->count());
    }

    public function test_503_response_is_recorded(): void
    {
        $url = '/test-503-'.uniqid();
        Route::get($url, fn () => abort(503));

        $this->actingAs($this->superAdmin)
            ->get($url)
            ->assertStatus(503);

        $this->assertDatabaseHas('error_logs', ['status_code' => 503]);
    }

    public function test_401_response_is_no_t_recorded(): void
    {
        // Unauthenticated request to a protected route = 401
        $this->getJson('/api/users')
            ->assertUnauthorized();

        $this->assertDatabaseMissing('error_logs', ['status_code' => 401]);
    }

    public function test_403_response_is_no_t_recorded(): void
    {
        $this->actingAs($this->student)
            ->getJson('/api/users')
            ->assertForbidden();

        $this->assertDatabaseMissing('error_logs', ['status_code' => 403]);
    }

    public function test_409_response_is_no_t_recorded(): void
    {
        // 409 is a business-rule conflict, not a failure.
        $url = '/test-409-'.uniqid();
        Route::post($url, fn () => abort(409, 'conflict'))
            ->middleware('auth');

        $this->actingAs($this->superAdmin)
            ->post($url)
            ->assertStatus(409);

        $this->assertDatabaseMissing('error_logs', ['status_code' => 409]);
    }

    public function test_422_response_is_no_t_recorded(): void
    {
        // 422 is validation — normal control flow.
        $url = '/test-422-'.uniqid();
        Route::post($url, fn () => abort(422, 'invalid'))
            ->middleware('auth');

        $this->actingAs($this->superAdmin)
            ->post($url)
            ->assertStatus(422);

        $this->assertDatabaseMissing('error_logs', ['status_code' => 422]);
    }

    public function test_2xx_response_is_no_t_recorded(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/dashboard')
            ->assertOk();

        $this->assertDatabaseMissing('error_logs', ['status_code' => 200]);
    }

    // -------------------------------------------------------------------------
    // What is stored — path only, no secrets
    // -------------------------------------------------------------------------

    public function test_query_string_is_dropped_from_url(): void
    {
        $url = '/secret/abc-'.uniqid();
        Route::get($url, fn () => abort(404));

        $requestUrl = $url.'?token=Bearer123&password=supersecret';

        $this->actingAs($this->superAdmin)
            ->get($requestUrl)
            ->assertNotFound();

        $this->assertDatabaseHas('error_logs', [
            'url' => $url,
            'status_code' => 404,
        ]);
        // The query string part must not be present.
        $this->assertDatabaseMissing('error_logs', ['url' => $requestUrl]);
    }

    // -------------------------------------------------------------------------
    // Authorization
    // -------------------------------------------------------------------------

    public function test_super_admin_can_access_web_index(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/error-logs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('ErrorLogs/Index'));
    }

    public function test_non_super_admin_cannot_access_web_index(): void
    {
        foreach ([$this->universityAdmin, $this->departmentAdmin, $this->lecturer, $this->student] as $user) {
            $this->actingAs($user)
                ->get('/error-logs')
                ->assertForbidden();
        }
    }

    public function test_super_admin_can_access_api_index(): void
    {
        $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/error-logs')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_non_super_admin_cannot_access_api_index(): void
    {
        foreach ([$this->universityAdmin, $this->departmentAdmin, $this->lecturer, $this->student] as $user) {
            $this->actingAs($user, 'sanctum')
                ->getJson('/api/error-logs')
                ->assertForbidden();
        }
    }

    public function test_unauthenticated_api_is_rejected(): void
    {
        $this->getJson('/api/error-logs')->assertUnauthorized();
    }

    public function test_unauthenticated_web_redirects_to_login(): void
    {
        $this->get('/error-logs')->assertRedirect('/login');
    }

    // -------------------------------------------------------------------------
    // Filters, search, sort, pagination
    // -------------------------------------------------------------------------

    public function test_search_matches_url_message_exception_class(): void
    {
        $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/error-logs?search=QueryException')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.exception_class', 'Illuminate\\Database\\QueryException');
    }

    public function test_filter_by_status_code(): void
    {
        $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/error-logs?filters[status_code]=404')
            ->assertOk()
            ->assertJsonCount(6, 'data') // Seeder has 6 404s
            ->assertJsonPath('data.0.status_code', 404)
            ->assertJsonPath('data.5.status_code', 404);
    }

    public function test_filter_by_status_group_server(): void
    {
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/error-logs?filters[status_group]=server')
            ->assertOk()
            ->assertJsonCount(5, 'data'); // Seeder has 5 server errors (500, 503, 502)

        foreach ($response->json('data') as $row) {
            $this->assertGreaterThanOrEqual(500, $row['status_code']);
        }
    }

    public function test_filter_by_status_group_not_found(): void
    {
        $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/error-logs?filters[status_group]=not_found')
            ->assertOk()
            ->assertJsonCount(6, 'data')
            ->assertJsonPath('data.0.status_code', 404)
            ->assertJsonPath('data.5.status_code', 404);
    }

    public function test_filter_by_method(): void
    {
        $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/error-logs?filters[method]=POST')
            ->assertOk()
            ->assertJsonCount(2, 'data') // Seeder has 2 POST errors
            ->assertJsonPath('data.0.method', 'POST')
            ->assertJsonPath('data.1.method', 'POST');
    }

    public function test_sort_by_created_at_desc_default(): void
    {
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/error-logs')
            ->assertOk();

        $data = $response->json('data');
        $this->assertGreaterThan($data[1]['id'], $data[0]['id']); // newest first
    }

    public function test_sort_by_status_code_asc(): void
    {
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/error-logs?sort_by=status_code&sort_dir=asc')
            ->assertOk();

        $data = $response->json('data');
        $this->assertLessThanOrEqual($data[1]['status_code'], $data[0]['status_code']);
    }

    public function test_pagination_respects_per_page_cap(): void
    {
        // Seeder inserts 11 rows. Cap is 100, so default 20 returns all.
        $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/error-logs?per_page=5')
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }

    // -------------------------------------------------------------------------
    // Append-only — no mutation endpoints
    // -------------------------------------------------------------------------

    public function test_no_create_endpoint_exists(): void
    {
        $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/error-logs', [])
            ->assertStatus(405); // Method Not Allowed (route exists for GET only)
    }

    public function test_no_update_endpoint_exists(): void
    {
        $errorLog = ErrorLog::query()->firstOrFail();

        $this->actingAs($this->superAdmin, 'sanctum')
            ->putJson("/api/error-logs/{$errorLog->id}", [])
            ->assertStatus(405);
    }

    public function test_no_delete_endpoint_exists(): void
    {
        $errorLog = ErrorLog::query()->firstOrFail();

        $this->actingAs($this->superAdmin, 'sanctum')
            ->deleteJson("/api/error-logs/{$errorLog->id}")
            ->assertStatus(405);
    }

    // -------------------------------------------------------------------------
    // Detail page
    // -------------------------------------------------------------------------

    public function test_super_admin_can_view_detail_web(): void
    {
        $errorLog = ErrorLog::query()->firstOrFail();

        $this->actingAs($this->superAdmin)
            ->get("/error-logs/{$errorLog->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('ErrorLogs/Show'));
    }

    public function test_super_admin_can_view_detail_api(): void
    {
        $errorLog = ErrorLog::query()->firstOrFail();

        $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson("/api/error-logs/{$errorLog->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $errorLog->id);
    }

    // -------------------------------------------------------------------------
    // Seeder idempotency
    // -------------------------------------------------------------------------

    public function test_seeder_is_idempotent(): void
    {
        $countBefore = ErrorLog::query()->count();

        $this->artisan('db:seed', ['--class' => 'ErrorLogSeeder']);

        $this->assertEquals($countBefore, ErrorLog::query()->count());
    }
}
