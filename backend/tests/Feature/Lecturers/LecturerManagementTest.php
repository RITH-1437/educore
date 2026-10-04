<?php

namespace Tests\Feature\Lecturers;

use App\Enums\Role;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\Role as RoleModel;
use App\Models\Semester;
use App\Models\University;
use App\Models\User;
use App\Services\LecturerService;
use Database\Seeders\LecturerSeeder;
use Database\Seeders\UniversityStructureSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Module 9.3 — Lecturer management.
 *
 * Role scoping (incl. a lecturer reading only their own profile), creating with
 * a new or an existing account, validation, account/profile synchronisation,
 * deactivate/reactivate, the section-assignment delete guard, and seeding.
 */
class LecturerManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $universityAdmin;

    private User $departmentAdmin;

    private User $lecturerUser;

    private User $student;

    private RoleModel $lecturerRole;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'root@test.test']);
        $this->universityAdmin = $this->userWithRole(Role::UniversityAdmin->value, 'dean@test.test');
        $this->departmentAdmin = $this->userWithRole(Role::DepartmentAdmin->value, 'unit@test.test');
        $this->lecturerRole = RoleModel::factory()->withSlug(Role::Lecturer->value)->create();
        $this->lecturerUser = User::factory()->create(['email' => 'teacher@test.test', 'role_id' => $this->lecturerRole->id]);
        $this->student = $this->userWithRole(Role::Student->value, 'pupil@test.test');

        $university = University::factory()->current()->create();
        $this->department = Department::factory()->create(['university_id' => $university->id]);
    }

    // ---------------------------------------------------------------- auth ---

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/lecturers')->assertUnauthorized();
        $this->get('/lecturers')->assertRedirect('/login');
    }

    public function test_student_is_forbidden(): void
    {
        $lecturer = $this->makeLecturer();

        $this->actingAs($this->student)->get('/lecturers')->assertForbidden();
        $this->actingAs($this->student)->getJson('/api/lecturers')->assertForbidden();
        $this->actingAs($this->student)->getJson("/api/lecturers/{$lecturer->id}")->assertForbidden();
    }

    public function test_lecturer_may_read_only_their_own_profile(): void
    {
        $own = $this->makeLecturer(['user_id' => $this->lecturerUser->id]);
        $other = $this->makeLecturer();

        $this->actingAs($this->lecturerUser)->getJson("/api/lecturers/{$own->id}")
            ->assertOk()->assertJsonPath('data.user.email', 'teacher@test.test');
        $this->actingAs($this->lecturerUser)->getJson("/api/lecturers/{$other->id}")->assertForbidden();
        $this->actingAs($this->lecturerUser)->getJson('/api/lecturers')->assertForbidden();
        $this->actingAs($this->lecturerUser)->get('/lecturers')->assertForbidden();
        $this->actingAs($this->lecturerUser)
            ->putJson("/api/lecturers/{$own->id}", $this->updatePayload($own))
            ->assertForbidden();
    }

    public function test_department_admin_may_read_but_not_write(): void
    {
        $lecturer = $this->makeLecturer();
        $this->departmentAdmin->update(['department_id' => $this->department->id]);
        $elsewhere = Lecturer::factory()->create();

        $this->actingAs($this->departmentAdmin)->getJson("/api/lecturers/{$elsewhere->id}")->assertForbidden();
        $this->actingAs($this->departmentAdmin)->getJson('/api/lecturers')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($this->departmentAdmin)->get('/lecturers')->assertOk();
        $this->actingAs($this->departmentAdmin)->getJson('/api/lecturers')->assertOk();
        $this->actingAs($this->departmentAdmin)->getJson("/api/lecturers/{$lecturer->id}")->assertOk();

        $this->actingAs($this->departmentAdmin)->postJson('/api/lecturers', $this->createPayload())->assertForbidden();
        $this->actingAs($this->departmentAdmin)->get("/lecturers/{$lecturer->id}/edit")->assertForbidden();
        $this->actingAs($this->departmentAdmin)->postJson("/api/lecturers/{$lecturer->id}/deactivate")->assertForbidden();
        $this->actingAs($this->departmentAdmin)->deleteJson("/api/lecturers/{$lecturer->id}")->assertForbidden();

        $this->assertDatabaseCount('lecturers', 2);
    }

    // ------------------------------------------------------------ screens ---

    public function test_index_screen_lists_lecturers_and_unlinked_accounts(): void
    {
        $this->makeLecturer();
        // `teacher@test.test` has the Lecturer role and no profile yet.

        $this->actingAs($this->superAdmin)
            ->get('/lecturers')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Lecturers/Index')
                ->has('lecturers.data', 1)
                ->has('departments.data', 1)
                ->has('employmentTypes', 4)
                ->has('unlinkedAccounts', 1)
                ->where('unlinkedAccounts.0.email', 'teacher@test.test')
                ->has('filters.search'));
    }

    public function test_edit_screen_receives_the_lecturer(): void
    {
        $lecturer = $this->makeLecturer(['staff_number' => 'LEC-9000']);

        $this->actingAs($this->universityAdmin)
            ->get("/lecturers/{$lecturer->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Lecturers/Edit')
                ->where('lecturer.staff_number', 'LEC-9000')
                ->where('lecturer.department.id', $this->department->id));
    }

    // --------------------------------------------------------------- create ---

    public function test_create_with_a_new_account(): void
    {
        $response = $this->actingAs($this->universityAdmin)
            ->postJson('/api/lecturers', $this->createPayload([
                'email' => 'new.lecturer@test.test',
                'first_name' => 'Sopheak',
                'last_name' => 'Ly',
                'title' => 'Dr.',
                'staff_number' => 'LEC-1001',
            ]))
            ->assertCreated()
            ->assertJsonPath('data.staff_number', 'LEC-1001')
            ->assertJsonPath('data.full_name', 'Dr. Sopheak Ly')
            ->assertJsonPath('data.user.email', 'new.lecturer@test.test')
            ->assertJsonPath('data.user.name', 'Sopheak Ly')
            ->assertJsonPath('data.is_active', true);

        $user = User::query()->where('email', 'new.lecturer@test.test')->firstOrFail();
        $this->assertTrue($user->isRole(Role::Lecturer->value));
        $this->assertTrue(Hash::check('secret-pass-1', $user->password));
        $this->assertSame($user->id, $response->json('data.user_id'));
    }

    public function test_create_by_linking_an_existing_lecturer_account(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('/api/lecturers', $this->profilePayload(['user_id' => $this->lecturerUser->id]))
            ->assertCreated()
            ->assertJsonPath('data.user_id', $this->lecturerUser->id);

        $this->assertDatabaseCount('users', 5); // no extra account was created
    }

    public function test_linking_rejects_non_lecturer_accounts_and_duplicates(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('/api/lecturers', $this->profilePayload(['user_id' => $this->student->id]))
            ->assertUnprocessable()->assertJsonValidationErrors(['user_id']);

        $this->makeLecturer(['user_id' => $this->lecturerUser->id]);

        $this->actingAs($this->superAdmin)
            ->postJson('/api/lecturers', $this->profilePayload(['user_id' => $this->lecturerUser->id]))
            ->assertUnprocessable()->assertJsonValidationErrors(['user_id']);
    }

    public function test_create_requires_an_account_or_credentials(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('/api/lecturers', $this->profilePayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_create_validates_profile_fields_and_uniqueness(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('/api/lecturers', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['staff_number', 'first_name', 'last_name', 'department_id']);

        $existing = $this->makeLecturer(['staff_number' => 'LEC-0001']);

        $this->actingAs($this->superAdmin)
            ->postJson('/api/lecturers', $this->createPayload([
                'staff_number' => 'LEC-0001',
                'email' => $existing->user->email,
                'employment_type' => 'freelance',
                'password_confirmation' => 'mismatch-000',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['staff_number', 'email', 'employment_type', 'password']);
    }

    public function test_create_rejects_an_archived_department(): void
    {
        $archived = Department::factory()->create(['university_id' => $this->department->university_id]);
        $archived->delete();

        $this->actingAs($this->superAdmin)
            ->postJson('/api/lecturers', $this->createPayload(['department_id' => $archived->id]))
            ->assertUnprocessable()->assertJsonValidationErrors(['department_id']);
    }

    public function test_failed_profile_creation_does_not_leave_an_orphan_account(): void
    {
        // The account is created first, inside the same transaction; a DB-level
        // failure on the profile must roll it back.
        DB::table('lecturers')->insert([
            'user_id' => $this->lecturerUser->id,
            'staff_number' => 'TAKEN',
            'first_name' => 'A',
            'last_name' => 'B',
            'department_id' => $this->department->id,
        ]);

        try {
            app(LecturerService::class)->create($this->createPayload([
                'email' => 'orphan@test.test',
                'staff_number' => 'TAKEN', // bypasses validation; violates the unique constraint
            ]));
            $this->fail('Expected a unique constraint violation.');
        } catch (UniqueConstraintViolationException) {
            $this->assertDatabaseMissing('users', ['email' => 'orphan@test.test']);
        }
    }

    // --------------------------------------------------------------- update ---

    public function test_update_syncs_the_account_name_and_contact(): void
    {
        $lecturer = $this->makeLecturer();

        $this->actingAs($this->universityAdmin)
            ->putJson("/api/lecturers/{$lecturer->id}", $this->updatePayload($lecturer, [
                'first_name' => 'Renamed',
                'last_name' => 'Person',
                'email' => 'renamed@test.test',
                'phone' => '+855 12 345 678',
                'employment_type' => 'visiting',
            ]))
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Renamed')
            ->assertJsonPath('data.employment_type', 'visiting')
            ->assertJsonPath('data.user.name', 'Renamed Person')
            ->assertJsonPath('data.user.email', 'renamed@test.test')
            ->assertJsonPath('data.user.phone', '+855 12 345 678');
    }

    public function test_update_keeps_own_staff_number_and_email_but_rejects_others(): void
    {
        $lecturer = $this->makeLecturer();
        $other = $this->makeLecturer();

        $this->actingAs($this->superAdmin)
            ->putJson("/api/lecturers/{$lecturer->id}", $this->updatePayload($lecturer, ['email' => $lecturer->user->email]))
            ->assertOk();

        $this->actingAs($this->superAdmin)
            ->putJson("/api/lecturers/{$lecturer->id}", $this->updatePayload($lecturer, [
                'staff_number' => $other->staff_number,
                'email' => $other->user->email,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['staff_number', 'email']);
    }

    public function test_web_create_and_update(): void
    {
        $this->actingAs($this->superAdmin)
            ->post('/lecturers', $this->createPayload(['staff_number' => 'LEC-2000']))
            ->assertRedirect('/lecturers')
            ->assertSessionHas('success');

        $lecturer = Lecturer::query()->where('staff_number', 'LEC-2000')->firstOrFail();

        $this->actingAs($this->superAdmin)
            ->put("/lecturers/{$lecturer->id}", $this->updatePayload($lecturer, ['position' => 'Dean']))
            ->assertRedirect('/lecturers');

        $this->assertSame('Dean', $lecturer->refresh()->position);

        $this->actingAs($this->superAdmin)
            ->from('/lecturers')
            ->post('/lecturers', [])
            ->assertSessionHasErrors(['staff_number', 'first_name', 'last_name', 'department_id']);
    }

    // -------------------------------------------------- list / filtering ---

    public function test_list_supports_search_filters_sort_and_page_cap(): void
    {
        $other = Department::factory()->create();
        $this->makeLecturer(['staff_number' => 'A-1', 'first_name' => 'Alpha', 'last_name' => 'Able', 'employment_type' => 'full_time']);
        $this->makeLecturer(['staff_number' => 'B-1', 'first_name' => 'Beta', 'last_name' => 'Baker', 'employment_type' => 'contract', 'is_active' => false]);
        $this->makeLecturer(['staff_number' => 'C-1', 'first_name' => 'Gamma', 'last_name' => 'Cole', 'department_id' => $other->id]);

        $this->actingAs($this->superAdmin)->getJson('/api/lecturers?search=alpha')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.staff_number', 'A-1');

        $this->actingAs($this->superAdmin)->getJson("/api/lecturers?filters[department_id]={$this->department->id}")
            ->assertOk()->assertJsonCount(2, 'data');

        $this->actingAs($this->superAdmin)->getJson("/api/lecturers?filters[department_id]={$other->id}")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.staff_number', 'C-1');

        $this->actingAs($this->superAdmin)->getJson('/api/lecturers?filters[employment_type]=contract')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.staff_number', 'B-1');

        $this->actingAs($this->superAdmin)->getJson('/api/lecturers?filters[is_active]=0')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($this->superAdmin)->getJson('/api/lecturers?sort_by=staff_number&sort_dir=desc')
            ->assertOk()->assertJsonPath('data.0.staff_number', 'C-1');

        $this->actingAs($this->superAdmin)->getJson('/api/lecturers?sort_by=password')
            ->assertOk()->assertJsonPath('data.0.last_name', 'Able');

        $this->actingAs($this->superAdmin)->getJson('/api/lecturers?per_page=5000')
            ->assertOk()->assertJsonPath('meta.per_page', 100);
    }

    public function test_search_matches_the_account_email(): void
    {
        $lecturer = $this->makeLecturer();
        $lecturer->user->update(['email' => 'findme@test.test']);

        $this->actingAs($this->superAdmin)->getJson('/api/lecturers?search=findme')
            ->assertOk()->assertJsonCount(1, 'data');
    }

    // ------------------------------------------- deactivate / delete ---

    public function test_deactivate_and_reactivate_mirror_onto_the_account(): void
    {
        $lecturer = $this->makeLecturer();

        $this->actingAs($this->universityAdmin)->postJson("/api/lecturers/{$lecturer->id}/deactivate")
            ->assertOk()->assertJsonPath('data.is_active', false)->assertJsonPath('data.user.is_active', false);
        $this->assertFalse($lecturer->user->refresh()->is_active);

        $this->actingAs($this->universityAdmin)->postJson("/api/lecturers/{$lecturer->id}/reactivate")
            ->assertOk()->assertJsonPath('data.is_active', true)->assertJsonPath('data.user.is_active', true);

        $this->actingAs($this->universityAdmin)->post("/lecturers/{$lecturer->id}/deactivate")->assertSessionHas('success');
        $this->assertFalse($lecturer->refresh()->is_active);
    }

    public function test_delete_removes_the_profile_and_keeps_the_account_inactive(): void
    {
        $lecturer = $this->makeLecturer();
        $userId = $lecturer->user_id;

        $this->actingAs($this->superAdmin)->deleteJson("/api/lecturers/{$lecturer->id}")->assertNoContent();

        $this->assertDatabaseMissing('lecturers', ['id' => $lecturer->id]);
        $this->assertDatabaseHas('users', ['id' => $userId, 'is_active' => false, 'deleted_at' => null]);
    }

    public function test_delete_is_blocked_while_assigned_to_sections(): void
    {
        $lecturer = $this->makeLecturer();

        // Sections have no module yet, so build the minimal FK chain directly.
        $sectionId = $this->insertSection();
        DB::table('section_lecturers')->insert(['section_id' => $sectionId, 'lecturer_id' => $lecturer->id]);

        $this->actingAs($this->superAdmin)->deleteJson("/api/lecturers/{$lecturer->id}")
            ->assertStatus(409)
            ->assertJsonPath('message', 'This lecturer is assigned to sections and cannot be deleted. Deactivate them instead.');

        $this->actingAs($this->superAdmin)->delete("/lecturers/{$lecturer->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('lecturers', ['id' => $lecturer->id]);
    }

    public function test_department_delete_guard_sees_lecturers(): void
    {
        $this->makeLecturer();

        $this->actingAs($this->superAdmin)->deleteJson("/api/departments/{$this->department->id}")->assertStatus(409);
    }

    // ------------------------------------------------------------- seeding ---

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(UniversityStructureSeeder::class);
        $this->seed(LecturerSeeder::class);
        $count = Lecturer::query()->count();

        $this->seed(LecturerSeeder::class);

        $this->assertSame($count, Lecturer::query()->count());
        $this->assertGreaterThanOrEqual(6, $count);
        $this->assertTrue(Lecturer::query()->firstOrFail()->user->isRole(Role::Lecturer->value));
    }

    // ------------------------------------------------------------- helpers ---

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeLecturer(array $attributes = []): Lecturer
    {
        return Lecturer::factory()->create(['department_id' => $this->department->id, ...$attributes]);
    }

    /**
     * Profile fields only (no account data).
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function profilePayload(array $overrides = []): array
    {
        return [
            'staff_number' => 'LEC-'.fake()->unique()->numerify('####'),
            'first_name' => 'Test',
            'last_name' => 'Lecturer',
            'title' => null,
            'department_id' => $this->department->id,
            'position' => 'Lecturer',
            'specialization' => 'Testing',
            'employment_type' => 'full_time',
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function createPayload(array $overrides = []): array
    {
        return $this->profilePayload([
            'email' => 'lec'.fake()->unique()->numerify('####').'@test.test',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
            ...$overrides,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function updatePayload(Lecturer $lecturer, array $overrides = []): array
    {
        return [
            'staff_number' => $lecturer->staff_number,
            'first_name' => $lecturer->first_name,
            'last_name' => $lecturer->last_name,
            'title' => $lecturer->title,
            'department_id' => $lecturer->department_id,
            'position' => $lecturer->position,
            'specialization' => $lecturer->specialization,
            'employment_type' => $lecturer->employment_type,
            ...$overrides,
        ];
    }

    /** A minimal course → offering → section chain for the delete guard. */
    private function insertSection(): int
    {
        $courseId = DB::table('courses')->insertGetId([
            'department_id' => $this->department->id, 'code' => 'SEC101', 'name' => 'Guard Course', 'credits' => 3,
        ]);
        $semesterId = Semester::factory()->create()->id;
        $offeringId = DB::table('course_offerings')->insertGetId(['course_id' => $courseId, 'semester_id' => $semesterId]);

        return DB::table('sections')->insertGetId(['course_offering_id' => $offeringId, 'code' => 'A', 'capacity' => 30]);
    }

    private function userWithRole(string $slug, string $email): User
    {
        return User::factory()->create([
            'email' => $email,
            'role_id' => RoleModel::factory()->withSlug($slug)->create()->id,
        ]);
    }
}
