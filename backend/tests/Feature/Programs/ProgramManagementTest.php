<?php

namespace Tests\Feature\Programs;

use App\Enums\Role;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Role as RoleModel;
use App\Models\University;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\UniversityStructureSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Module 9.5 — Program management.
 *
 * Role scoping, CRUD over web and API, uniqueness (validation and database
 * layers), archive-versus-delete, and the child-reference delete guards.
 */
class ProgramManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $universityAdmin;

    private User $facultyAdmin;

    private User $lecturer;

    private User $student;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'root@test.test']);
        $this->universityAdmin = $this->userWithRole(Role::UniversityAdmin->value, 'dean@test.test');
        $this->facultyAdmin = $this->userWithRole(Role::FacultyAdmin->value, 'unit@test.test');
        $this->lecturer = $this->userWithRole(Role::Lecturer->value, 'teacher@test.test');
        $this->student = $this->userWithRole(Role::Student->value, 'pupil@test.test');

        $university = University::factory()->current()->create();
        $faculty = Faculty::factory()->create(['university_id' => $university->id]);
        $this->department = Department::factory()->create(['faculty_id' => $faculty->id]);
    }

    // ---------------------------------------------------------------- auth ---

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/programs')->assertUnauthorized();
        $this->get('/programs')->assertRedirect('/login');
    }

    public function test_student_and_lecturer_are_forbidden(): void
    {
        foreach ([$this->student, $this->lecturer] as $user) {
            $this->actingAs($user)->get('/programs')->assertForbidden();
            $this->actingAs($user)->getJson('/api/programs')->assertForbidden();
            $this->actingAs($user)->postJson('/api/programs', $this->payload())->assertForbidden();
        }
    }

    public function test_faculty_admin_may_read_but_not_write(): void
    {
        $program = Program::factory()->create(['department_id' => $this->department->id]);

        $this->actingAs($this->facultyAdmin)->get('/programs')->assertOk();
        $this->actingAs($this->facultyAdmin)->getJson('/api/programs')->assertOk();
        $this->actingAs($this->facultyAdmin)->getJson("/api/programs/{$program->id}")->assertOk();

        $this->actingAs($this->facultyAdmin)->post('/programs', $this->payload())->assertForbidden();
        $this->actingAs($this->facultyAdmin)->postJson('/api/programs', $this->payload())->assertForbidden();
        $this->actingAs($this->facultyAdmin)->get("/programs/{$program->id}/edit")->assertForbidden();
        $this->actingAs($this->facultyAdmin)->postJson("/api/programs/{$program->id}/archive")->assertForbidden();
        $this->actingAs($this->facultyAdmin)->deleteJson("/api/programs/{$program->id}")->assertForbidden();

        $this->assertDatabaseCount('programs', 1);
    }

    // ------------------------------------------------------------ screens ---

    public function test_index_screen_lists_programs_with_lookups(): void
    {
        Program::factory()->count(2)->create(['department_id' => $this->department->id]);

        $this->actingAs($this->superAdmin)
            ->get('/programs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Programs/Index')
                ->has('programs.data', 2)
                ->has('faculties.data', 1)
                ->has('departments.data', 1)
                ->has('degreeLevels', 4)
                ->has('filters.search'));
    }

    public function test_edit_screen_receives_the_program(): void
    {
        $program = Program::factory()->create(['department_id' => $this->department->id, 'code' => 'BSCS']);

        $this->actingAs($this->universityAdmin)
            ->get("/programs/{$program->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Programs/Edit')
                ->where('program.code', 'BSCS')
                ->where('program.department.id', $this->department->id));
    }

    // ---------------------------------------------------------- web CRUD ---

    public function test_university_admin_can_create_update_and_delete_via_web(): void
    {
        $this->actingAs($this->universityAdmin)
            ->post('/programs', $this->payload(['code' => 'BSCS']))
            ->assertRedirect('/programs')
            ->assertSessionHas('success');

        $program = Program::query()->where('code', 'BSCS')->firstOrFail();
        $this->assertTrue($program->is_active);

        $this->actingAs($this->universityAdmin)
            ->put("/programs/{$program->id}", $this->payload(['code' => 'BSCS', 'name' => 'Renamed Program']))
            ->assertRedirect('/programs');

        $this->assertDatabaseHas('programs', ['id' => $program->id, 'name' => 'Renamed Program']);

        $this->actingAs($this->universityAdmin)
            ->delete("/programs/{$program->id}")
            ->assertRedirect('/programs');

        $this->assertDatabaseMissing('programs', ['id' => $program->id]);
    }

    public function test_web_validation_errors_are_returned_to_the_form(): void
    {
        $this->actingAs($this->superAdmin)
            ->from('/programs')
            ->post('/programs', [])
            ->assertSessionHasErrors(['department_id', 'code', 'name', 'degree_level']);
    }

    // ---------------------------------------------------------- API CRUD ---

    public function test_api_crud_cycle(): void
    {
        $created = $this->actingAs($this->superAdmin)
            ->postJson('/api/programs', $this->payload(['code' => 'MSCS', 'degree_level' => 'master', 'credits_required' => 48.5]))
            ->assertCreated()
            ->assertJsonPath('data.code', 'MSCS')
            ->assertJsonPath('data.degree_level', 'master')
            ->assertJsonPath('data.credits_required', 48.5)
            ->assertJsonPath('data.department.id', $this->department->id)
            ->assertJsonPath('data.is_active', true);

        $id = $created->json('data.id');

        $this->actingAs($this->superAdmin)->getJson("/api/programs/{$id}")
            ->assertOk()->assertJsonPath('data.code', 'MSCS');

        $this->actingAs($this->superAdmin)
            ->patchJson("/api/programs/{$id}", $this->payload(['code' => 'MSCS', 'name' => 'Updated']))
            ->assertOk()->assertJsonPath('data.name', 'Updated');

        $this->actingAs($this->superAdmin)->deleteJson("/api/programs/{$id}")->assertNoContent();
        $this->actingAs($this->superAdmin)->getJson("/api/programs/{$id}")->assertNotFound();
    }

    public function test_api_returns_404_for_unknown_program(): void
    {
        $this->actingAs($this->superAdmin)->getJson('/api/programs/999999')->assertNotFound();
    }

    // -------------------------------------------------------- validation ---

    public function test_required_fields_and_enum_are_validated(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('/api/programs', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['department_id', 'code', 'name', 'degree_level']);

        $this->actingAs($this->superAdmin)
            ->postJson('/api/programs', $this->payload(['degree_level' => 'wizard', 'duration_years' => 0, 'credits_required' => -1]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['degree_level', 'duration_years', 'credits_required']);
    }

    public function test_code_must_be_unique(): void
    {
        Program::factory()->create(['department_id' => $this->department->id, 'code' => 'BSCS']);

        $this->actingAs($this->superAdmin)
            ->postJson('/api/programs', $this->payload(['code' => 'BSCS', 'name' => 'Another name']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    }

    public function test_name_is_unique_within_a_department_only(): void
    {
        Program::factory()->create(['department_id' => $this->department->id, 'name' => 'Bachelor of Computing']);

        $this->actingAs($this->superAdmin)
            ->postJson('/api/programs', $this->payload(['name' => 'Bachelor of Computing']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $other = Department::factory()->create();

        $this->actingAs($this->superAdmin)
            ->postJson('/api/programs', $this->payload([
                'department_id' => $other->id,
                'name' => 'Bachelor of Computing',
            ]))
            ->assertCreated();
    }

    public function test_update_may_keep_its_own_code_and_name(): void
    {
        $program = Program::factory()->create(['department_id' => $this->department->id]);

        $this->actingAs($this->superAdmin)
            ->putJson("/api/programs/{$program->id}", $this->payload([
                'code' => $program->code,
                'name' => $program->name,
                'duration_years' => 3,
            ]))
            ->assertOk()
            ->assertJsonPath('data.duration_years', 3);
    }

    public function test_update_without_department_checks_name_in_current_department(): void
    {
        Program::factory()->create(['department_id' => $this->department->id, 'name' => 'Taken Name']);
        $program = Program::factory()->create(['department_id' => $this->department->id]);

        $this->actingAs($this->superAdmin)
            ->putJson("/api/programs/{$program->id}", [
                'code' => $program->code,
                'name' => 'Taken Name',
                'degree_level' => 'bachelor',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_program_cannot_be_created_under_an_archived_department(): void
    {
        $archived = Department::factory()->create(['faculty_id' => $this->department->faculty_id]);
        $archived->delete();

        $this->actingAs($this->superAdmin)
            ->postJson('/api/programs', $this->payload(['department_id' => $archived->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['department_id']);
    }

    public function test_program_may_move_to_another_department(): void
    {
        $program = Program::factory()->create(['department_id' => $this->department->id]);
        $target = Department::factory()->create();

        $this->actingAs($this->superAdmin)
            ->putJson("/api/programs/{$program->id}", $this->payload([
                'code' => $program->code,
                'name' => $program->name,
                'department_id' => $target->id,
            ]))
            ->assertOk()
            ->assertJsonPath('data.department_id', $target->id);
    }

    public function test_database_enforces_uniqueness_when_validation_is_bypassed(): void
    {
        Program::factory()->create(['department_id' => $this->department->id, 'code' => 'DUP', 'name' => 'Same']);

        $this->expectException(UniqueConstraintViolationException::class);
        Program::factory()->create(['department_id' => $this->department->id, 'code' => 'DUP2', 'name' => 'Same']);
    }

    // --------------------------------------------------- list / filtering ---

    public function test_list_supports_search_filters_sorting_and_page_cap(): void
    {
        $other = Department::factory()->create();
        Program::factory()->create(['department_id' => $this->department->id, 'code' => 'AAA', 'name' => 'Alpha Studies', 'degree_level' => 'bachelor']);
        Program::factory()->create(['department_id' => $this->department->id, 'code' => 'BBB', 'name' => 'Beta Studies', 'degree_level' => 'master', 'is_active' => false]);
        Program::factory()->create(['department_id' => $other->id, 'code' => 'CCC', 'name' => 'Gamma Studies', 'degree_level' => 'bachelor']);

        $this->actingAs($this->superAdmin)->getJson('/api/programs?search=alpha')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.code', 'AAA');

        $this->actingAs($this->superAdmin)->getJson("/api/programs?filters[department_id]={$this->department->id}")
            ->assertOk()->assertJsonCount(2, 'data');

        $this->actingAs($this->superAdmin)->getJson("/api/programs?filters[faculty_id]={$other->faculty_id}")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.code', 'CCC');

        $this->actingAs($this->superAdmin)->getJson('/api/programs?filters[degree_level]=master')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.code', 'BBB');

        $this->actingAs($this->superAdmin)->getJson('/api/programs?filters[is_active]=0')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.code', 'BBB');

        $this->actingAs($this->superAdmin)->getJson('/api/programs?sort_by=code&sort_dir=desc')
            ->assertOk()->assertJsonPath('data.0.code', 'CCC');

        // A non-whitelisted sort column falls back to the default instead of reaching SQL.
        $this->actingAs($this->superAdmin)->getJson('/api/programs?sort_by=password;drop')
            ->assertOk()->assertJsonPath('data.0.code', 'AAA');

        $this->actingAs($this->superAdmin)->getJson('/api/programs?per_page=5000')
            ->assertOk()->assertJsonPath('meta.per_page', 100);
    }

    // ------------------------------------------------- archive / delete ---

    public function test_archive_and_reactivate(): void
    {
        $program = Program::factory()->create(['department_id' => $this->department->id]);

        $this->actingAs($this->universityAdmin)
            ->postJson("/api/programs/{$program->id}/archive")
            ->assertOk()->assertJsonPath('data.is_active', false);

        $this->actingAs($this->universityAdmin)
            ->postJson("/api/programs/{$program->id}/reactivate")
            ->assertOk()->assertJsonPath('data.is_active', true);

        $this->actingAs($this->universityAdmin)
            ->post("/programs/{$program->id}/archive")
            ->assertSessionHas('success');

        $this->assertFalse($program->refresh()->is_active);
    }

    public function test_plain_update_cannot_change_active_state(): void
    {
        $program = Program::factory()->create(['department_id' => $this->department->id]);

        $this->actingAs($this->superAdmin)
            ->putJson("/api/programs/{$program->id}", $this->payload([
                'code' => $program->code,
                'name' => $program->name,
                'is_active' => false,
            ]))
            ->assertOk();

        $this->assertTrue($program->refresh()->is_active);
    }

    public function test_delete_is_blocked_while_students_reference_the_program(): void
    {
        $program = Program::factory()->create(['department_id' => $this->department->id]);
        $studentId = $this->insertStudent();

        DB::table('student_programs')->insert([
            'student_id' => $studentId,
            'program_id' => $program->id,
            'started_on' => '2026-01-01',
            'status' => 'active',
        ]);

        $this->actingAs($this->superAdmin)
            ->deleteJson("/api/programs/{$program->id}")
            ->assertStatus(409)
            ->assertJsonPath('message', 'This program still has students and cannot be deleted. Archive it instead.');

        $this->assertDatabaseHas('programs', ['id' => $program->id]);

        // On web the same rule surfaces as a flash error instead of JSON.
        $this->actingAs($this->superAdmin)
            ->delete("/programs/{$program->id}")
            ->assertSessionHas('error');
    }

    public function test_delete_is_blocked_while_curriculum_courses_reference_the_program(): void
    {
        $program = Program::factory()->create(['department_id' => $this->department->id]);
        $courseId = DB::table('courses')->insertGetId([
            'department_id' => $this->department->id,
            'code' => 'CS101',
            'name' => 'Intro to Computing',
            'credits' => 3,
        ]);

        DB::table('course_programs')->insert(['course_id' => $courseId, 'program_id' => $program->id]);

        // The schema cascades `course_programs`; the service must refuse first.
        $this->actingAs($this->superAdmin)
            ->deleteJson("/api/programs/{$program->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('course_programs', ['program_id' => $program->id]);
    }

    public function test_department_delete_guard_now_sees_programs(): void
    {
        Program::factory()->create(['department_id' => $this->department->id]);

        $this->actingAs($this->superAdmin)
            ->deleteJson("/api/departments/{$this->department->id}")
            ->assertStatus(409);
    }

    // ------------------------------------------------------------- seeding ---

    public function test_seeder_is_idempotent_and_attaches_to_departments(): void
    {
        $this->seed(UniversityStructureSeeder::class);
        $this->seed(ProgramSeeder::class);
        $count = Program::query()->count();

        $this->seed(ProgramSeeder::class);

        $this->assertSame($count, Program::query()->count());
        $this->assertGreaterThanOrEqual(7, $count);
        $this->assertSame(
            'CSE',
            Program::query()->where('code', 'BSCS')->firstOrFail()->department->code,
        );
    }

    // ------------------------------------------------------------- helpers ---

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'department_id' => $this->department->id,
            'code' => 'PRG'.fake()->unique()->numerify('###'),
            'name' => 'Program '.fake()->unique()->numerify('###'),
            'degree_level' => 'bachelor',
            'duration_years' => 4,
            'credits_required' => 120,
            ...$overrides,
        ];
    }

    private function userWithRole(string $slug, string $email): User
    {
        return User::factory()->create([
            'email' => $email,
            'role_id' => RoleModel::factory()->withSlug($slug)->create()->id,
        ]);
    }

    /** Minimal `students` row (no Student model exists until module 9.2). */
    private function insertStudent(): int
    {
        return DB::table('students')->insertGetId([
            'user_id' => $this->student->id,
            'student_number' => 'S-0001',
            'first_name' => 'Test',
            'last_name' => 'Student',
        ]);
    }
}
