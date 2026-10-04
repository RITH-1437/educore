<?php

namespace Tests\Feature\Departments;

use App\Enums\Role;
use App\Models\Department;
use App\Models\Role as RoleModel;
use App\Models\University;
use App\Models\User;
use Database\Seeders\UniversityStructureSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * University and Department management (`docs/39_Department-Only-Structure-Report.md`;
 * formerly module 9.4 with a faculty level).
 *
 * Covers the happy paths plus the domain rules that matter: role scoping,
 * the single "current university" invariant, archive-versus-delete, the
 * database-level uniqueness constraints and the child-reference delete guards.
 */
class DepartmentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $universityAdmin;

    private User $departmentAdmin;

    private User $lecturer;

    private User $student;

    private University $university;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'root@test.test']);
        $this->universityAdmin = $this->userWithRole(Role::UniversityAdmin->value, 'dean@test.test');
        $this->departmentAdmin = $this->userWithRole(Role::DepartmentAdmin->value, 'unit@test.test');
        $this->lecturer = $this->userWithRole(Role::Lecturer->value, 'teacher@test.test');
        $this->student = $this->userWithRole(Role::Student->value, 'pupil@test.test');

        $this->university = University::factory()->current()->create([
            'code' => 'ITC',
            'name' => 'Institute of Technology Cambodia',
        ]);
    }

    // ---------------------------------------------------------------- auth ---

    public function test_unauthenticated_api_request_is_rejected(): void
    {
        $this->getJson('/api/departments')->assertUnauthorized();
    }

    public function test_unauthenticated_web_request_redirects_to_login(): void
    {
        $this->get('/departments')->assertRedirect('/login');
    }

    public function test_student_is_forbidden_from_the_structure(): void
    {
        $this->actingAs($this->student)->get('/departments')->assertForbidden();
        $this->actingAs($this->student)->get('/universities')->assertForbidden();
        $this->actingAs($this->student)->getJson('/api/departments')->assertForbidden();
    }

    public function test_lecturer_is_forbidden_from_the_structure(): void
    {
        $this->actingAs($this->lecturer)->getJson('/api/departments')->assertForbidden();
        $this->actingAs($this->lecturer)->getJson('/api/universities')->assertForbidden();
    }

    public function test_department_admin_may_read_the_structure(): void
    {
        $this->actingAs($this->departmentAdmin)->get('/departments')->assertOk();
        $this->actingAs($this->departmentAdmin)->get('/universities')->assertOk();
        $this->actingAs($this->departmentAdmin)->getJson('/api/departments')->assertOk();
    }

    public function test_department_admin_cannot_write_the_structure(): void
    {
        $department = Department::factory()->create(['university_id' => $this->university->id]);

        $this->actingAs($this->departmentAdmin)->post('/departments', $this->departmentPayload())->assertForbidden();
        $this->actingAs($this->departmentAdmin)->postJson('/api/departments', $this->departmentPayload())->assertForbidden();
        $this->actingAs($this->departmentAdmin)->post('/universities', $this->universityPayload())->assertForbidden();
        $this->actingAs($this->departmentAdmin)->postJson('/api/universities', $this->universityPayload())->assertForbidden();
        $this->actingAs($this->departmentAdmin)->get("/departments/{$department->id}/edit")->assertForbidden();
        $this->actingAs($this->departmentAdmin)->get("/universities/{$this->university->id}/edit")->assertForbidden();

        $this->assertDatabaseCount('departments', 1);
        $this->assertDatabaseCount('universities', 1);
    }

    // ------------------------------------------------------------ screens ---

    public function test_super_admin_can_view_the_departments_index(): void
    {
        Department::factory()->create(['university_id' => $this->university->id]);

        $this->actingAs($this->superAdmin)
            ->get('/departments')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Departments/Index')
                ->has('departments.data', 1)
                ->where('departments.data.0.university.code', 'ITC')
                ->has('universities', 1)
                ->has('universities.0.id') // a plain list for the form and the filter
                ->has('filters.search'));
    }

    public function test_super_admin_can_view_the_universities_index(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/universities')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Universities/Index')
                ->has('universities.data', 1)
                ->has('filters.search'));
    }

    public function test_super_admin_can_filter_universities_by_status(): void
    {
        $otherUniversity = University::factory()->create(['is_current' => false]);

        $this->actingAs($this->superAdmin)
            ->get('/universities?status=current')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Universities/Index')
                ->has('universities.data', 1)
                ->where('universities.data.0.id', $this->university->id)
                ->where('filters.status', 'current'));

        $this->actingAs($this->superAdmin)
            ->get('/universities?status=other')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Universities/Index')
                ->has('universities.data', 1)
                ->where('universities.data.0.id', $otherUniversity->id)
                ->where('filters.status', 'other'));
    }

    public function test_super_admin_can_view_the_department_edit_screen(): void
    {
        $department = Department::factory()->create(['university_id' => $this->university->id]);

        $this->actingAs($this->superAdmin)
            ->get("/departments/{$department->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Departments/Edit')
                ->where('department.id', $department->id)
                ->where('department.programs_count', 0)
                ->has('universities.0.id'));
    }

    public function test_super_admin_can_view_the_university_edit_screen(): void
    {
        $this->actingAs($this->superAdmin)
            ->get("/universities/{$this->university->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Universities/Edit')
                ->where('university.id', $this->university->id)
                ->where('university.departments_count', 0));
    }

    // ------------------------------------------------------------- create ---

    public function test_university_admin_can_create_a_university(): void
    {
        $this->actingAs($this->universityAdmin)
            ->postJson('/api/universities', $this->universityPayload(['code' => 'RUPP']))
            ->assertCreated()
            ->assertJsonPath('data.code', 'RUPP');

        $this->assertDatabaseHas('universities', ['code' => 'RUPP']);
    }

    public function test_creating_a_university_requires_a_code_and_name(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('/api/universities', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code', 'name']);

        $this->assertDatabaseCount('universities', 1);
    }

    public function test_university_code_must_be_unique(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('/api/universities', $this->universityPayload())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        $this->assertDatabaseCount('universities', 1);
    }

    public function test_university_admin_can_create_a_department_in_the_current_university(): void
    {
        $this->actingAs($this->universityAdmin)
            ->postJson('/api/departments', ['code' => 'CSE', 'name' => 'Department of Computer Science'])
            ->assertCreated()
            ->assertJsonPath('data.code', 'CSE')
            ->assertJsonPath('data.university_id', $this->university->id)
            ->assertJsonPath('data.university.code', 'ITC');

        $this->assertDatabaseHas('departments', ['university_id' => $this->university->id, 'code' => 'CSE']);
    }

    public function test_a_department_can_be_created_in_a_chosen_university(): void
    {
        $other = University::factory()->create(['code' => 'RUPP']);

        $this->actingAs($this->superAdmin)
            ->postJson('/api/departments', $this->departmentPayload(['university_id' => $other->id]))
            ->assertCreated()
            ->assertJsonPath('data.university_id', $other->id);
    }

    public function test_a_department_needs_a_university(): void
    {
        $this->university->update(['is_current' => false]);

        $this->actingAs($this->superAdmin)
            ->postJson('/api/departments', ['code' => 'CSE', 'name' => 'Computer Science'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['university_id']);

        $this->assertDatabaseCount('departments', 0);
    }

    public function test_a_department_rejects_an_unknown_university(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('/api/departments', $this->departmentPayload(['university_id' => 9999]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['university_id']);
    }

    public function test_department_name_must_be_unique_within_its_university(): void
    {
        Department::factory()->create(['university_id' => $this->university->id, 'name' => 'Department of Computer Science']);

        $this->actingAs($this->superAdmin)
            ->postJson('/api/departments', ['code' => 'CSE2', 'name' => 'Department of Computer Science'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_the_same_department_name_may_exist_in_two_universities(): void
    {
        $other = University::factory()->create(['code' => 'RUPP']);
        Department::factory()->create(['university_id' => $this->university->id, 'name' => 'Department of Languages']);

        $this->actingAs($this->superAdmin)
            ->postJson('/api/departments', ['university_id' => $other->id, 'code' => 'LANG', 'name' => 'Department of Languages'])
            ->assertCreated();

        $this->assertDatabaseCount('departments', 2);
    }

    // -------------------------------------------------------------- read ----

    public function test_the_department_list_is_paginated_and_searchable(): void
    {
        Department::factory()->create(['university_id' => $this->university->id, 'code' => 'CSE', 'name' => 'Department of Computer Science']);
        Department::factory()->create(['university_id' => $this->university->id, 'code' => 'PHY', 'name' => 'Department of Physics']);

        $this->actingAs($this->superAdmin)
            ->getJson('/api/departments?search=Computer')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.code', 'CSE');
    }

    public function test_departments_can_be_filtered_by_university_and_active_state(): void
    {
        $other = University::factory()->create(['code' => 'RUPP']);
        Department::factory()->create(['university_id' => $this->university->id]);
        Department::factory()->inactive()->create(['university_id' => $this->university->id]);
        Department::factory()->create(['university_id' => $other->id]);

        $this->actingAs($this->superAdmin)
            ->getJson("/api/departments?filters[university_id]={$this->university->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->actingAs($this->superAdmin)
            ->getJson('/api/departments?filters[is_active]=0')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_active', false);
    }

    public function test_unknown_records_return_404(): void
    {
        $this->actingAs($this->superAdmin)->getJson('/api/departments/9999')->assertNotFound();
        $this->actingAs($this->superAdmin)->getJson('/api/universities/9999')->assertNotFound();
        $this->actingAs($this->superAdmin)->get('/departments/9999/edit')->assertNotFound();
    }

    // ------------------------------------------------------------- update ---

    public function test_university_admin_can_update_a_department(): void
    {
        $department = Department::factory()->create(['university_id' => $this->university->id]);

        $this->actingAs($this->universityAdmin)
            ->putJson("/api/departments/{$department->id}", [
                'code' => $department->code,
                'name' => 'Department of Computing',
                'head_name' => 'Dr. New Head',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Department of Computing');

        $this->assertDatabaseHas('departments', ['id' => $department->id, 'head_name' => 'Dr. New Head']);
    }

    public function test_updating_a_department_keeps_its_name_unique_in_its_university(): void
    {
        $duplicate = Department::factory()->create(['university_id' => $this->university->id, 'name' => 'Department of Physics']);
        $department = Department::factory()->create(['university_id' => $this->university->id]);

        // No `university_id` submitted: the uniqueness scope must fall back to the
        // university the department already belongs to, not to NULL.
        $this->actingAs($this->superAdmin)
            ->putJson("/api/departments/{$department->id}", ['code' => $department->code, 'name' => 'Department of Physics'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        $this->assertDatabaseHas('departments', ['id' => $duplicate->id, 'name' => 'Department of Physics']);
    }

    public function test_a_department_can_be_moved_to_another_university(): void
    {
        $other = University::factory()->create(['code' => 'RUPP']);
        $department = Department::factory()->create(['university_id' => $this->university->id]);

        $this->actingAs($this->superAdmin)
            ->putJson("/api/departments/{$department->id}", ['university_id' => $other->id, 'code' => $department->code, 'name' => $department->name])
            ->assertOk()
            ->assertJsonPath('data.university_id', $other->id);

        $this->assertDatabaseHas('departments', ['id' => $department->id, 'university_id' => $other->id]);
    }

    public function test_promoting_a_university_clears_the_previous_current_one(): void
    {
        $next = University::factory()->create(['code' => 'RUPP']);

        $this->actingAs($this->superAdmin)
            ->postJson("/api/universities/{$next->id}/current")
            ->assertOk()
            ->assertJsonPath('data.is_current', true);

        $this->assertSame(0, University::query()->where('is_current', true)->whereKey($this->university->id)->count());
        $this->assertSame(1, University::query()->where('is_current', true)->count());
    }

    public function test_creating_a_current_university_also_clears_the_previous_one(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('/api/universities', $this->universityPayload(['code' => 'RUPP', 'is_current' => true]))
            ->assertCreated()
            ->assertJsonPath('data.is_current', true);

        $this->assertSame(1, University::query()->where('is_current', true)->count());
        $this->assertSame(0, University::query()->where('is_current', true)->whereKey($this->university->id)->count());
    }

    // ------------------------------------------------- archive and delete ---

    public function test_archiving_a_department_keeps_the_row(): void
    {
        $department = Department::factory()->create(['university_id' => $this->university->id]);

        $this->actingAs($this->superAdmin)
            ->postJson("/api/departments/{$department->id}/archive")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('departments', ['id' => $department->id, 'is_active' => false, 'deleted_at' => null]);
    }

    public function test_an_archived_department_can_be_reactivated(): void
    {
        $department = Department::factory()->inactive()->create(['university_id' => $this->university->id]);

        $this->actingAs($this->superAdmin)
            ->postJson("/api/departments/{$department->id}/reactivate")
            ->assertOk()
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('departments', ['id' => $department->id, 'is_active' => true]);
    }

    public function test_a_department_referenced_by_a_course_cannot_be_deleted(): void
    {
        $department = Department::factory()->create(['university_id' => $this->university->id]);
        DB::table('courses')->insert(['department_id' => $department->id, 'code' => 'CS101', 'name' => 'Introduction to Programming', 'credits' => 3]);

        $this->actingAs($this->superAdmin)
            ->deleteJson("/api/departments/{$department->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    public function test_an_unreferenced_department_can_be_soft_deleted(): void
    {
        $department = Department::factory()->create(['university_id' => $this->university->id]);

        $this->actingAs($this->superAdmin)
            ->deleteJson("/api/departments/{$department->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('departments', ['id' => $department->id]);
    }

    public function test_the_current_university_cannot_be_deleted(): void
    {
        $this->actingAs($this->superAdmin)
            ->deleteJson("/api/universities/{$this->university->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('universities', ['id' => $this->university->id]);
    }

    public function test_a_university_with_departments_cannot_be_deleted(): void
    {
        $other = University::factory()->create(['code' => 'RUPP']);
        Department::factory()->create(['university_id' => $other->id]);

        $this->actingAs($this->superAdmin)
            ->deleteJson("/api/universities/{$other->id}")
            ->assertStatus(409)
            ->assertJsonPath('message', 'This university still has 1 department(s) and cannot be deleted.');

        $this->assertDatabaseHas('universities', ['id' => $other->id]);
    }

    public function test_a_soft_deleted_department_still_blocks_deleting_its_university(): void
    {
        $other = University::factory()->create(['code' => 'RUPP']);
        Department::factory()->create(['university_id' => $other->id])->delete();

        // A soft-deleted department still holds a foreign key, so the guard must
        // count trashed rows too rather than letting the database throw.
        $this->actingAs($this->superAdmin)
            ->deleteJson("/api/universities/{$other->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('universities', ['id' => $other->id]);
    }

    public function test_an_empty_university_can_be_deleted(): void
    {
        $other = University::factory()->create(['code' => 'RUPP']);

        $this->actingAs($this->superAdmin)
            ->deleteJson("/api/universities/{$other->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('universities', ['id' => $other->id]);
    }

    // --------------------------------------------------------------- web ----

    public function test_super_admin_can_create_a_department_from_the_web_form(): void
    {
        $this->actingAs($this->superAdmin)
            ->post('/departments', $this->departmentPayload())
            ->assertRedirect('/departments')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('departments', ['code' => 'CSE', 'university_id' => $this->university->id]);
    }

    public function test_super_admin_can_update_archive_and_reactivate_from_the_web(): void
    {
        $department = Department::factory()->create(['university_id' => $this->university->id]);

        $this->actingAs($this->superAdmin)
            ->put("/departments/{$department->id}", ['code' => $department->code, 'name' => 'Department of Data Science'])
            ->assertRedirect('/departments')
            ->assertSessionHas('success');
        $this->actingAs($this->superAdmin)->from('/departments')->post("/departments/{$department->id}/archive")->assertRedirect('/departments');
        $this->assertFalse($department->refresh()->is_active);
        $this->actingAs($this->superAdmin)->from('/departments')->post("/departments/{$department->id}/reactivate")->assertRedirect('/departments');
        $this->assertTrue($department->refresh()->is_active);
        $this->assertSame('Department of Data Science', $department->name);
    }

    public function test_a_duplicate_department_name_is_rejected_by_the_web_form(): void
    {
        Department::factory()->create(['university_id' => $this->university->id, 'name' => 'Department of Physics']);

        $this->actingAs($this->superAdmin)
            ->from('/departments')
            ->post('/departments', ['code' => 'PHY2', 'name' => 'Department of Physics'])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('departments', 1);
    }

    public function test_a_business_rule_failure_shows_as_a_flash_error_on_the_web(): void
    {
        $department = Department::factory()->create(['university_id' => $this->university->id]);
        DB::table('courses')->insert(['department_id' => $department->id, 'code' => 'CS101', 'name' => 'Introduction to Programming', 'credits' => 3]);

        $this->actingAs($this->superAdmin)
            ->from('/departments')
            ->delete("/departments/{$department->id}")
            ->assertRedirect('/departments')
            ->assertSessionHas('error');
    }

    // ------------------------------------------------------ db constraints ---

    public function test_the_database_rejects_two_departments_with_the_same_name_in_one_university(): void
    {
        $this->expectException(UniqueConstraintViolationException::class);

        Department::factory()->create(['university_id' => $this->university->id, 'name' => 'Physics']);
        Department::factory()->create(['university_id' => $this->university->id, 'name' => 'Physics']);
    }

    // -------------------------------------------------------------- seed ----

    public function test_the_structure_seeder_is_idempotent(): void
    {
        $this->seed(UniversityStructureSeeder::class);
        $this->seed(UniversityStructureSeeder::class);

        $this->assertSame(1, University::query()->where('is_current', true)->count());
        $this->assertSame(6, Department::query()->count());
        $this->assertSame(6, Department::query()->whereHas('university', fn ($q) => $q->where('code', 'ITC'))->count());
    }

    // ------------------------------------------------------------ helpers ---

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function universityPayload(array $overrides = []): array
    {
        return array_merge([
            'code' => 'ITC',
            'name' => 'Institute of Technology Cambodia',
            'short_name' => 'ITC',
            'address' => 'Prey Saem District, Kandal Province',
            'phone' => '+855 23 883 222',
            'email' => 'info@educore.kh',
            'website' => 'https://www.educore.kh',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function departmentPayload(array $overrides = []): array
    {
        return array_merge([
            'code' => 'CSE',
            'name' => 'Department of Computer Science',
            'head_name' => 'Dr. Dara Lim',
        ], $overrides);
    }

    private function userWithRole(string $slug, string $email): User
    {
        return User::factory()->create([
            'email' => $email,
            'role_id' => RoleModel::factory()->withSlug($slug)->create()->id,
        ]);
    }
}
