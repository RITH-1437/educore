<?php

namespace Tests\Feature\FacultyDepartment;

use App\Enums\Role;
use App\Models\Department;
use App\Models\Faculty;
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
 * Module 9.4 — University, Faculty and Department management.
 *
 * Covers the happy paths plus the domain rules that matter: role scoping,
 * the single "current university" invariant, archive-versus-delete, the
 * database-level uniqueness constraints and the child-reference delete guards.
 */
class FacultyDepartmentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $universityAdmin;

    private User $facultyAdmin;

    private User $lecturer;

    private User $student;

    private University $university;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'root@test.test']);
        $this->universityAdmin = $this->userWithRole(Role::UniversityAdmin->value, 'dean@test.test');
        $this->facultyAdmin = $this->userWithRole(Role::FacultyAdmin->value, 'unit@test.test');
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
        $this->getJson('/api/faculties')->assertUnauthorized();
    }

    public function test_unauthenticated_web_request_redirects_to_login(): void
    {
        $this->get('/faculties')->assertRedirect('/login');
    }

    public function test_student_is_forbidden_from_the_structure(): void
    {
        $this->actingAs($this->student)->get('/faculties')->assertForbidden();
        $this->actingAs($this->student)->get('/universities')->assertForbidden();
        $this->actingAs($this->student)->getJson('/api/faculties')->assertForbidden();
        $this->actingAs($this->student)->getJson('/api/departments')->assertForbidden();
    }

    public function test_lecturer_is_forbidden_from_the_structure(): void
    {
        $this->actingAs($this->lecturer)->getJson('/api/faculties')->assertForbidden();
        $this->actingAs($this->lecturer)->getJson('/api/universities')->assertForbidden();
    }

    public function test_faculty_admin_may_read_the_structure(): void
    {
        Faculty::factory()->count(2)->create(['university_id' => $this->university->id]);

        $this->actingAs($this->facultyAdmin)->get('/faculties')->assertOk();
        $this->actingAs($this->facultyAdmin)->get('/universities')->assertOk();
        $this->actingAs($this->facultyAdmin)->getJson('/api/faculties')->assertOk();
        $this->actingAs($this->facultyAdmin)->getJson('/api/departments')->assertOk();
    }

    public function test_faculty_admin_cannot_write_the_structure(): void
    {
        $faculty = Faculty::factory()->create(['university_id' => $this->university->id]);

        $this->actingAs($this->facultyAdmin)
            ->post('/faculties', $this->facultyPayload())
            ->assertForbidden();

        $this->actingAs($this->facultyAdmin)
            ->postJson('/api/faculties', $this->facultyPayload())
            ->assertForbidden();

        $this->actingAs($this->facultyAdmin)
            ->post('/universities', $this->universityPayload())
            ->assertForbidden();

        $this->actingAs($this->facultyAdmin)
            ->postJson('/api/universities', $this->universityPayload())
            ->assertForbidden();

        $this->actingAs($this->facultyAdmin)
            ->get("/faculties/{$faculty->id}/edit")
            ->assertForbidden();

        $this->actingAs($this->facultyAdmin)
            ->get("/universities/{$this->university->id}/edit")
            ->assertForbidden();

        $this->assertDatabaseCount('faculties', 1);
        $this->assertDatabaseCount('universities', 1);
    }

    // ------------------------------------------------------------ screens ---

    public function test_super_admin_can_view_the_faculties_index(): void
    {
        $faculty = Faculty::factory()->create(['university_id' => $this->university->id]);
        Department::factory()->create(['faculty_id' => $faculty->id]);

        $this->actingAs($this->superAdmin)
            ->get('/faculties')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Faculties/Index')
                ->has('faculties.data', 1)
                ->has('departments', 1)
                ->has('universities.data', 1)
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

    public function test_super_admin_can_view_the_faculty_edit_screen(): void
    {
        $faculty = Faculty::factory()->create(['university_id' => $this->university->id]);

        $this->actingAs($this->superAdmin)
            ->get("/faculties/{$faculty->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Faculties/Edit')
                ->where('faculty.id', $faculty->id)
                ->has('universities.data'));
    }

    public function test_super_admin_can_view_the_university_edit_screen(): void
    {
        $this->actingAs($this->superAdmin)
            ->get("/universities/{$this->university->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Universities/Edit')
                ->where('university.id', $this->university->id)
                ->where('university.faculties_count', 0));
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

    public function test_university_admin_can_create_a_faculty(): void
    {
        $this->actingAs($this->universityAdmin)
            ->postJson('/api/faculties', $this->facultyPayload())
            ->assertCreated()
            ->assertJsonPath('data.code', 'ENG');

        $this->assertDatabaseHas('faculties', [
            'code' => 'ENG',
            'name' => 'Faculty of Engineering',
            'university_id' => $this->university->id,
        ]);
    }

    public function test_faculty_name_must_be_unique_across_the_university(): void
    {
        Faculty::factory()->create([
            'university_id' => $this->university->id,
            'name' => 'Faculty of Engineering',
        ]);

        $this->actingAs($this->superAdmin)
            ->postJson('/api/faculties', $this->facultyPayload())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_faculty_rejects_an_unknown_university(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('/api/faculties', $this->facultyPayload(['university_id' => 9999]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['university_id']);
    }

    public function test_university_admin_can_create_a_department(): void
    {
        $faculty = Faculty::factory()->create(['university_id' => $this->university->id]);

        $this->actingAs($this->universityAdmin)
            ->postJson('/api/departments', [
                'faculty_id' => $faculty->id,
                'code' => 'CSE',
                'name' => 'Department of Computer Science',
            ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'CSE');

        $this->assertDatabaseHas('departments', [
            'faculty_id' => $faculty->id,
            'code' => 'CSE',
        ]);
    }

    public function test_department_requires_a_faculty(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('/api/departments', ['code' => 'CSE', 'name' => 'Computer Science'])
            ->assertStatus(422);

        $this->assertDatabaseCount('departments', 0);
    }

    public function test_department_name_must_be_unique_within_its_faculty(): void
    {
        $faculty = Faculty::factory()->create(['university_id' => $this->university->id]);
        Department::factory()->create([
            'faculty_id' => $faculty->id,
            'name' => 'Department of Computer Science',
        ]);

        $this->actingAs($this->superAdmin)
            ->postJson('/api/departments', [
                'faculty_id' => $faculty->id,
                'code' => 'CSE2',
                'name' => 'Department of Computer Science',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_the_same_department_name_may_exist_in_two_faculties(): void
    {
        $first = Faculty::factory()->create(['university_id' => $this->university->id]);
        $second = Faculty::factory()->create(['university_id' => $this->university->id]);

        Department::factory()->create([
            'faculty_id' => $first->id,
            'name' => 'Department of Languages',
        ]);

        $this->actingAs($this->superAdmin)
            ->postJson('/api/departments', [
                'faculty_id' => $second->id,
                'code' => 'LANG',
                'name' => 'Department of Languages',
            ])
            ->assertCreated();

        $this->assertDatabaseCount('departments', 2);
    }

    // -------------------------------------------------------------- read ----

    public function test_the_faculty_list_is_paginated_and_searchable(): void
    {
        Faculty::factory()->create(['university_id' => $this->university->id, 'code' => 'ENG', 'name' => 'Faculty of Engineering']);
        Faculty::factory()->create(['university_id' => $this->university->id, 'code' => 'SCI', 'name' => 'Faculty of Science']);

        $this->actingAs($this->superAdmin)
            ->getJson('/api/faculties?search=Engineering')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.code', 'ENG');
    }

    public function test_faculties_can_be_filtered_by_university_and_active_state(): void
    {
        Faculty::factory()->create(['university_id' => $this->university->id]);
        Faculty::factory()->inactive()->create(['university_id' => $this->university->id]);

        $this->actingAs($this->superAdmin)
            ->getJson("/api/faculties?filters[university_id]={$this->university->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->actingAs($this->superAdmin)
            ->getJson('/api/faculties?filters[is_active]=0')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_active', false);
    }

    public function test_departments_can_be_scoped_to_a_faculty(): void
    {
        $first = Faculty::factory()->create(['university_id' => $this->university->id]);
        $second = Faculty::factory()->create(['university_id' => $this->university->id]);

        Department::factory()->create(['faculty_id' => $first->id]);
        Department::factory()->count(2)->create(['faculty_id' => $second->id]);

        $this->actingAs($this->superAdmin)
            ->getJson("/api/departments?filters[faculty_id]={$first->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_unknown_records_return_404(): void
    {
        $this->actingAs($this->superAdmin)->getJson('/api/faculties/9999')->assertNotFound();
        $this->actingAs($this->superAdmin)->getJson('/api/departments/9999')->assertNotFound();
        $this->actingAs($this->superAdmin)->getJson('/api/universities/9999')->assertNotFound();
    }

    public function test_a_department_cannot_be_reached_through_the_wrong_faculty(): void
    {
        $faculty = Faculty::factory()->create(['university_id' => $this->university->id]);
        $other = Faculty::factory()->create(['university_id' => $this->university->id]);
        $department = Department::factory()->create(['faculty_id' => $faculty->id]);

        $this->actingAs($this->superAdmin)
            ->postJson("/faculties/{$other->id}/departments/{$department->id}/archive")
            ->assertNotFound();
    }

    public function test_the_faculty_tree_returns_active_records_only(): void
    {
        $faculty = Faculty::factory()->create(['university_id' => $this->university->id]);
        $archived = Faculty::factory()->inactive()->create(['university_id' => $this->university->id]);

        Department::factory()->create(['faculty_id' => $faculty->id]);
        Department::factory()->inactive()->create(['faculty_id' => $faculty->id]);
        Department::factory()->create(['faculty_id' => $archived->id]);

        $response = $this->actingAs($this->superAdmin)->getJson('/api/faculties-tree')->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame($faculty->id, $response->json('data.0.id'));
        $this->assertCount(1, $response->json('data.0.departments'));
    }

    // ------------------------------------------------------------- update ---

    public function test_university_admin_can_update_a_faculty(): void
    {
        $faculty = Faculty::factory()->create(['university_id' => $this->university->id]);

        $this->actingAs($this->universityAdmin)
            ->putJson("/api/faculties/{$faculty->id}", [
                'university_id' => $this->university->id,
                'code' => 'ENG',
                'name' => 'Faculty of Engineering and Technology',
                'dean_name' => 'Dr. New Dean',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Faculty of Engineering and Technology');

        $this->assertDatabaseHas('faculties', ['id' => $faculty->id, 'dean_name' => 'Dr. New Dean']);
    }

    public function test_updating_a_department_keeps_its_name_unique_in_the_current_faculty(): void
    {
        $faculty = Faculty::factory()->create(['university_id' => $this->university->id]);
        $duplicate = Department::factory()->create([
            'faculty_id' => $faculty->id,
            'name' => 'Department of Physics',
        ]);
        $department = Department::factory()->create(['faculty_id' => $faculty->id]);

        // No `faculty_id` submitted: the uniqueness scope must fall back to the
        // faculty the department already belongs to, not to NULL.
        $this->actingAs($this->superAdmin)
            ->putJson("/api/departments/{$department->id}", [
                'code' => $department->code,
                'name' => 'Department of Physics',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        $this->assertDatabaseHas('departments', ['id' => $duplicate->id, 'name' => 'Department of Physics']);
    }

    public function test_a_department_can_be_moved_to_another_faculty(): void
    {
        $first = Faculty::factory()->create(['university_id' => $this->university->id]);
        $second = Faculty::factory()->create(['university_id' => $this->university->id]);
        $department = Department::factory()->create(['faculty_id' => $first->id]);

        $this->actingAs($this->superAdmin)
            ->putJson("/api/departments/{$department->id}", [
                'faculty_id' => $second->id,
                'code' => $department->code,
                'name' => $department->name,
            ])
            ->assertOk()
            ->assertJsonPath('data.faculty_id', $second->id);

        $this->assertDatabaseHas('departments', ['id' => $department->id, 'faculty_id' => $second->id]);
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

    public function test_archiving_a_faculty_keeps_the_row_and_its_children(): void
    {
        $faculty = Faculty::factory()->create(['university_id' => $this->university->id]);
        $department = Department::factory()->create(['faculty_id' => $faculty->id]);

        $this->actingAs($this->superAdmin)
            ->postJson("/api/faculties/{$faculty->id}/archive")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        // Archive is not delete: the row stays, only `is_active` flips, so the
        // department keeps a valid parent.
        $this->assertDatabaseHas('faculties', [
            'id' => $faculty->id,
            'is_active' => false,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    public function test_archiving_a_department_keeps_the_row(): void
    {
        $faculty = Faculty::factory()->create(['university_id' => $this->university->id]);
        $department = Department::factory()->create(['faculty_id' => $faculty->id]);

        $this->actingAs($this->superAdmin)
            ->postJson("/api/departments/{$department->id}/archive")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'is_active' => false,
            'deleted_at' => null,
        ]);
    }

    public function test_an_archived_faculty_can_be_reactivated(): void
    {
        $faculty = Faculty::factory()->inactive()->create(['university_id' => $this->university->id]);

        $this->actingAs($this->superAdmin)
            ->postJson("/api/faculties/{$faculty->id}/reactivate")
            ->assertOk()
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('faculties', ['id' => $faculty->id, 'is_active' => true]);
    }

    public function test_a_faculty_with_departments_cannot_be_deleted(): void
    {
        $faculty = Faculty::factory()->create(['university_id' => $this->university->id]);
        Department::factory()->create(['faculty_id' => $faculty->id]);

        $this->actingAs($this->superAdmin)
            ->deleteJson("/api/faculties/{$faculty->id}")
            ->assertStatus(409)
            ->assertJsonPath('message', 'This faculty still has 1 department(s) and cannot be deleted. Archive it instead.');

        $this->assertDatabaseHas('faculties', ['id' => $faculty->id]);
    }

    public function test_an_empty_faculty_can_be_soft_deleted(): void
    {
        $faculty = Faculty::factory()->create(['university_id' => $this->university->id]);

        $this->actingAs($this->superAdmin)
            ->deleteJson("/api/faculties/{$faculty->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('faculties', ['id' => $faculty->id]);
    }

    public function test_a_department_referenced_by_a_course_cannot_be_deleted(): void
    {
        $faculty = Faculty::factory()->create(['university_id' => $this->university->id]);
        $department = Department::factory()->create(['faculty_id' => $faculty->id]);

        DB::table('courses')->insert([
            'department_id' => $department->id,
            'code' => 'CS101',
            'name' => 'Introduction to Programming',
            'credits' => 3,
        ]);

        $this->actingAs($this->superAdmin)
            ->deleteJson("/api/departments/{$department->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    public function test_an_unreferenced_department_can_be_soft_deleted(): void
    {
        $faculty = Faculty::factory()->create(['university_id' => $this->university->id]);
        $department = Department::factory()->create(['faculty_id' => $faculty->id]);

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

    public function test_a_university_with_faculties_cannot_be_deleted(): void
    {
        $other = University::factory()->create(['code' => 'RUPP']);
        Faculty::factory()->create(['university_id' => $other->id]);

        $this->actingAs($this->superAdmin)
            ->deleteJson("/api/universities/{$other->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('universities', ['id' => $other->id]);
    }

    public function test_a_soft_deleted_faculty_still_blocks_deleting_its_university(): void
    {
        $other = University::factory()->create(['code' => 'RUPP']);
        $faculty = Faculty::factory()->create(['university_id' => $other->id]);
        $faculty->delete();

        // A soft-deleted faculty still holds a foreign key, so the guard must
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

    public function test_super_admin_can_create_a_faculty_from_the_web_form(): void
    {
        $this->actingAs($this->superAdmin)
            ->post('/faculties', $this->facultyPayload())
            ->assertRedirect('/faculties')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('faculties', ['code' => 'ENG']);
    }

    public function test_super_admin_can_add_a_department_through_the_nested_form(): void
    {
        $faculty = Faculty::factory()->create(['university_id' => $this->university->id]);

        $this->actingAs($this->superAdmin)
            ->post("/faculties/{$faculty->id}/departments", [
                'code' => 'CSE',
                'name' => 'Department of Computer Science',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('departments', ['faculty_id' => $faculty->id, 'code' => 'CSE']);
    }

    public function test_a_duplicate_department_name_is_rejected_by_the_web_form(): void
    {
        $faculty = Faculty::factory()->create(['university_id' => $this->university->id]);
        Department::factory()->create([
            'faculty_id' => $faculty->id,
            'name' => 'Department of Physics',
        ]);

        $this->actingAs($this->superAdmin)
            ->from('/faculties')
            ->post("/faculties/{$faculty->id}/departments", [
                'code' => 'PHY2',
                'name' => 'Department of Physics',
            ])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('departments', 1);
    }

    public function test_a_business_rule_failure_shows_as_a_flash_error_on_the_web(): void
    {
        $faculty = Faculty::factory()->create(['university_id' => $this->university->id]);
        Department::factory()->create(['faculty_id' => $faculty->id]);

        $this->actingAs($this->superAdmin)
            ->from('/faculties')
            ->delete("/faculties/{$faculty->id}")
            ->assertRedirect('/faculties')
            ->assertSessionHas('error');
    }

    // ------------------------------------------------------ db constraints ---

    public function test_the_database_rejects_two_faculties_with_the_same_name(): void
    {
        $this->expectException(UniqueConstraintViolationException::class);

        Faculty::factory()->create([
            'university_id' => $this->university->id,
            'name' => 'Faculty of Engineering',
        ]);
        Faculty::factory()->create([
            'university_id' => $this->university->id,
            'name' => 'Faculty of Engineering',
        ]);
    }

    public function test_the_database_rejects_two_departments_with_the_same_name_in_one_faculty(): void
    {
        $this->expectException(UniqueConstraintViolationException::class);

        $faculty = Faculty::factory()->create(['university_id' => $this->university->id]);

        Department::factory()->create(['faculty_id' => $faculty->id, 'name' => 'Physics']);
        Department::factory()->create(['faculty_id' => $faculty->id, 'name' => 'Physics']);
    }

    // -------------------------------------------------------------- seed ----

    public function test_the_structure_seeder_is_idempotent(): void
    {
        $this->seed(UniversityStructureSeeder::class);
        $this->seed(UniversityStructureSeeder::class);

        $this->assertSame(1, University::query()->where('is_current', true)->count());
        $this->assertSame(3, Faculty::query()->count());
        $this->assertSame(6, Department::query()->count());
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
    private function facultyPayload(array $overrides = []): array
    {
        return array_merge([
            'university_id' => $this->university->id,
            'code' => 'ENG',
            'name' => 'Faculty of Engineering',
            'dean_name' => 'Dr. Sokha Chan',
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
