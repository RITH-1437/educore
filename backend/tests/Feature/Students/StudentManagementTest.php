<?php

namespace Tests\Feature\Students;

use App\Enums\Role;
use App\Models\Department;
use App\Models\Program;
use App\Models\Role as RoleModel;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentProgram;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\StudentSeeder;
use Database\Seeders\UniversityStructureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Module 9.2 — Student management: role scoping, account creation/linking,
 * validation, status lifecycle, program transfer, delete guards, seeding.
 */
class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $universityAdmin;

    private User $departmentAdmin;

    private User $lecturer;

    private User $studentUser;

    private Program $program;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->universityAdmin = $this->userWithRole(Role::UniversityAdmin->value);
        $this->departmentAdmin = $this->userWithRole(Role::DepartmentAdmin->value);
        $this->lecturer = $this->userWithRole(Role::Lecturer->value);
        $this->studentUser = $this->userWithRole(Role::Student->value, 'me@test.test');
        $this->program = Program::factory()->create(['department_id' => Department::factory()->create()->id]);
    }

    // ---------------------------------------------------------------- auth ---

    public function test_unauthenticated_and_wrong_roles_are_rejected(): void
    {
        $this->getJson('/api/students')->assertUnauthorized();
        $this->get('/students')->assertRedirect('/login');

        $this->actingAs($this->lecturer)->getJson('/api/students')->assertForbidden();
        $this->actingAs($this->studentUser)->getJson('/api/students')->assertForbidden();
        $this->actingAs($this->studentUser)->get('/students')->assertForbidden();
    }

    public function test_student_reads_only_their_own_profile(): void
    {
        $own = $this->makeStudent(['user_id' => $this->studentUser->id]);
        $other = $this->makeStudent();

        $this->actingAs($this->studentUser)->getJson("/api/students/{$own->id}")
            ->assertOk()->assertJsonPath('data.user.email', 'me@test.test')
            ->assertJsonMissingPath('data.user.password');
        $this->actingAs($this->studentUser)->getJson("/api/students/{$other->id}")->assertForbidden();
        $this->actingAs($this->studentUser)->putJson("/api/students/{$own->id}", $this->updatePayload($own))->assertForbidden();
    }

    public function test_department_admin_reads_but_cannot_write(): void
    {
        $student = $this->makeStudent();
        $this->departmentAdmin->update(['department_id' => $this->program->department_id]);
        $elsewhere = Student::factory()->create();

        $this->actingAs($this->departmentAdmin)->getJson("/api/students/{$elsewhere->id}")->assertForbidden();
        $this->actingAs($this->departmentAdmin)->getJson('/api/students')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($this->departmentAdmin)->get('/students')->assertOk();
        $this->actingAs($this->departmentAdmin)->getJson("/api/students/{$student->id}")->assertOk();
        $this->actingAs($this->departmentAdmin)->postJson('/api/students', $this->createPayload())->assertForbidden();
        $this->actingAs($this->departmentAdmin)->postJson("/api/students/{$student->id}/status", ['status' => 'suspended'])->assertForbidden();
        $this->actingAs($this->departmentAdmin)->deleteJson("/api/students/{$student->id}")->assertForbidden();
    }

    // ------------------------------------------------------------ screens ---

    public function test_index_and_edit_screens(): void
    {
        $student = $this->makeStudent(['student_number' => 'ITC-0001']);

        $this->actingAs($this->superAdmin)->get('/students')->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Students/Index')
                ->has('students.data', 1)
                ->has('programs', 1)
                ->has('statuses', 5)
                ->has('unlinkedAccounts', 1)
                ->where('unlinkedAccounts.0.email', 'me@test.test'));

        $this->actingAs($this->superAdmin)->get("/students/{$student->id}/edit")->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Students/Edit')
                ->where('student.student_number', 'ITC-0001')
                ->where('student.current_program.program.id', $this->program->id)
                ->has('student.program_history', 1));
    }

    // --------------------------------------------------------------- create ---

    public function test_create_with_new_account_opens_a_program_period(): void
    {
        $this->actingAs($this->universityAdmin)
            ->postJson('/api/students', $this->createPayload([
                'student_number' => 'ITC-2026-0100',
                'email' => 'new.student@test.test',
                'first_name' => 'Dara',
                'last_name' => 'Ly',
                'enrollment_date' => '2026-09-01',
            ]))
            ->assertCreated()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.user.name', 'Dara Ly')
            ->assertJsonPath('data.user.is_active', true)
            ->assertJsonPath('data.current_program.program.id', $this->program->id)
            ->assertJsonPath('data.current_program.started_on', '2026-09-01');

        $this->assertTrue(User::query()->where('email', 'new.student@test.test')->firstOrFail()->isRole(Role::Student->value));
    }

    public function test_create_by_linking_an_existing_student_account(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('/api/students', $this->profilePayload(['user_id' => $this->studentUser->id]))
            ->assertCreated()->assertJsonPath('data.user_id', $this->studentUser->id);

        $this->actingAs($this->superAdmin)
            ->postJson('/api/students', $this->profilePayload(['user_id' => $this->lecturer->id]))
            ->assertUnprocessable()->assertJsonValidationErrors(['user_id']);
    }

    public function test_create_validation_and_uniqueness(): void
    {
        $this->actingAs($this->superAdmin)->postJson('/api/students', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['student_number', 'first_name', 'last_name', 'program_id', 'email', 'password']);

        $existing = $this->makeStudent(['student_number' => 'ITC-0001', 'national_id' => 'N-1']);
        $archived = Program::factory()->inactive()->create();

        $this->actingAs($this->superAdmin)
            ->postJson('/api/students', $this->createPayload([
                'student_number' => 'ITC-0001',
                'national_id' => 'N-1',
                'email' => $existing->user->email,
                'program_id' => $archived->id,
                'gender' => 'unknown',
                'date_of_birth' => now()->addDay()->toDateString(),
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['student_number', 'national_id', 'email', 'program_id', 'gender', 'date_of_birth']);

        $this->actingAs($this->superAdmin)
            ->postJson('/api/students', $this->createPayload(['student_number' => 'bad id!']))
            ->assertUnprocessable()->assertJsonValidationErrors(['student_number']);
    }

    // --------------------------------------------------------------- update ---

    public function test_update_syncs_account_and_keeps_own_unique_values(): void
    {
        $student = $this->makeStudent();

        $this->actingAs($this->superAdmin)
            ->putJson("/api/students/{$student->id}", $this->updatePayload($student, [
                'first_name' => 'New', 'last_name' => 'Name', 'email' => 'changed@test.test',
            ]))
            ->assertOk()
            ->assertJsonPath('data.user.name', 'New Name')
            ->assertJsonPath('data.user.email', 'changed@test.test');

        $other = $this->makeStudent();
        $this->actingAs($this->superAdmin)
            ->putJson("/api/students/{$student->id}", $this->updatePayload($student, ['student_number' => $other->student_number]))
            ->assertUnprocessable()->assertJsonValidationErrors(['student_number']);
    }

    // --------------------------------------------------------------- status ---

    public function test_status_transitions_follow_the_rules_and_gate_sign_in(): void
    {
        $student = $this->makeStudent();

        $this->actingAs($this->superAdmin)->postJson("/api/students/{$student->id}/status", ['status' => 'suspended'])
            ->assertOk()->assertJsonPath('data.status', 'suspended')->assertJsonPath('data.user.is_active', false);

        // suspended → graduated is not allowed.
        $this->actingAs($this->superAdmin)->postJson("/api/students/{$student->id}/status", ['status' => 'graduated'])
            ->assertStatus(409);

        $this->actingAs($this->superAdmin)->postJson("/api/students/{$student->id}/status", ['status' => 'active'])
            ->assertOk()->assertJsonPath('data.user.is_active', true);
    }

    public function test_graduation_closes_the_program_and_is_final(): void
    {
        $student = $this->makeStudent();

        $this->actingAs($this->superAdmin)
            ->postJson("/api/students/{$student->id}/status", ['status' => 'graduated', 'effective_on' => '2029-06-30'])
            ->assertOk()
            ->assertJsonPath('data.user.is_active', true) // graduates keep sign-in
            ->assertJsonPath('data.current_program', null)
            ->assertJsonPath('data.program_history.0.status', 'completed')
            ->assertJsonPath('data.program_history.0.ended_on', '2029-06-30')
            ->assertJsonPath('data.allowed_statuses', []);

        $this->actingAs($this->superAdmin)->postJson("/api/students/{$student->id}/status", ['status' => 'active'])
            ->assertStatus(409);
    }

    public function test_withdrawal_closes_the_program_as_withdrawn(): void
    {
        $student = $this->makeStudent();

        $this->actingAs($this->superAdmin)->postJson("/api/students/{$student->id}/status", ['status' => 'withdrawn'])
            ->assertOk()->assertJsonPath('data.user.is_active', false)
            ->assertJsonPath('data.program_history.0.status', 'withdrawn');
    }

    public function test_closing_date_cannot_precede_the_program_start(): void
    {
        $student = $this->makeStudent();

        $this->actingAs($this->superAdmin)
            ->postJson("/api/students/{$student->id}/status", ['status' => 'graduated', 'effective_on' => '2020-01-01'])
            ->assertUnprocessable()->assertJsonValidationErrors(['effective_on']);

        $this->assertSame('active', $student->refresh()->status);
    }

    // -------------------------------------------------------------- program ---

    public function test_program_transfer_keeps_history(): void
    {
        $student = $this->makeStudent();
        $target = Program::factory()->create();

        $this->actingAs($this->superAdmin)
            ->postJson("/api/students/{$student->id}/program", ['program_id' => $target->id, 'effective_on' => '2026-02-01', 'notes' => 'Changed track'])
            ->assertOk()
            ->assertJsonPath('data.current_program.program.id', $target->id)
            ->assertJsonPath('data.current_program.started_on', '2026-02-01')
            ->assertJsonCount(2, 'data.program_history');

        $this->assertDatabaseHas('student_programs', [
            'student_id' => $student->id, 'program_id' => $this->program->id, 'status' => 'transferred',
        ]);
        $this->assertSame(1, StudentProgram::query()->where('student_id', $student->id)->where('status', 'active')->count());
    }

    public function test_program_transfer_guards(): void
    {
        $student = $this->makeStudent();

        $this->actingAs($this->superAdmin)->postJson("/api/students/{$student->id}/program", ['program_id' => $this->program->id])
            ->assertUnprocessable()->assertJsonValidationErrors(['program_id']);

        $this->actingAs($this->superAdmin)->postJson("/api/students/{$student->id}/program", ['program_id' => Program::factory()->inactive()->create()->id])
            ->assertUnprocessable()->assertJsonValidationErrors(['program_id']);

        $suspended = $this->makeStudent(['status' => 'suspended']);
        $this->actingAs($this->superAdmin)->postJson("/api/students/{$suspended->id}/program", ['program_id' => Program::factory()->create()->id])
            ->assertStatus(409);
    }

    public function test_program_transfer_is_blocked_by_open_enrollments(): void
    {
        $student = $this->makeStudent();
        $this->insertEnrollment($student, 'confirmed');

        $this->actingAs($this->superAdmin)->postJson("/api/students/{$student->id}/program", ['program_id' => Program::factory()->create()->id])
            ->assertStatus(409);
    }

    // --------------------------------------------------------------- delete ---

    public function test_delete_removes_profile_and_program_rows_but_keeps_account(): void
    {
        $student = $this->makeStudent();
        $userId = $student->user_id;

        $this->actingAs($this->superAdmin)->deleteJson("/api/students/{$student->id}")->assertNoContent();

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
        $this->assertDatabaseMissing('student_programs', ['student_id' => $student->id]);
        $this->assertDatabaseHas('users', ['id' => $userId, 'is_active' => false]);
    }

    public function test_delete_is_blocked_by_academic_history(): void
    {
        $student = $this->makeStudent();
        $this->insertEnrollment($student, 'completed');

        $this->actingAs($this->superAdmin)->deleteJson("/api/students/{$student->id}")
            ->assertStatus(409)
            ->assertJsonPath('message', 'This student has enrollments and cannot be deleted. Change their status instead.');

        $this->actingAs($this->superAdmin)->delete("/students/{$student->id}")->assertSessionHas('error');
    }

    public function test_program_delete_guard_sees_students(): void
    {
        $this->makeStudent();

        $this->actingAs($this->superAdmin)->deleteJson("/api/programs/{$this->program->id}")->assertStatus(409);
    }

    // ------------------------------------------------------ list / web / seed ---

    public function test_list_filters_search_and_sort(): void
    {
        $other = Program::factory()->create();
        $this->makeStudent(['student_number' => 'AAA-1', 'first_name' => 'Alpha']);
        $this->makeStudent(['student_number' => 'BBB-1', 'status' => 'suspended']);
        $this->makeStudent(['student_number' => 'CCC-1'], $other);

        $this->actingAs($this->superAdmin)->getJson('/api/students?search=alpha')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($this->superAdmin)->getJson("/api/students?filters[program_id]={$other->id}")->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($this->superAdmin)->getJson("/api/students?filters[department_id]={$other->department_id}")->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($this->superAdmin)->getJson('/api/students?filters[status]=suspended')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($this->superAdmin)->getJson('/api/students?sort_by=student_number&sort_dir=desc')->assertOk()->assertJsonPath('data.0.student_number', 'CCC-1');
        $this->actingAs($this->superAdmin)->getJson('/api/students?sort_by=password')->assertOk()->assertJsonPath('data.0.student_number', 'AAA-1');
    }

    public function test_web_create_redirects_to_edit_and_status_change_flashes(): void
    {
        $response = $this->actingAs($this->superAdmin)->post('/students', $this->createPayload(['student_number' => 'WEB-0001']));
        $student = Student::query()->where('student_number', 'WEB-0001')->firstOrFail();
        $response->assertRedirect("/students/{$student->id}/edit");

        $this->actingAs($this->superAdmin)->post("/students/{$student->id}/status", ['status' => 'inactive'])->assertSessionHas('success');
        $this->assertSame('inactive', $student->refresh()->status);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(UniversityStructureSeeder::class);
        $this->seed(ProgramSeeder::class);
        $this->seed(StudentSeeder::class);
        $students = Student::query()->count();
        $periods = StudentProgram::query()->count();

        $this->seed(StudentSeeder::class);

        $this->assertGreaterThanOrEqual(10, $students);
        $this->assertSame($students, Student::query()->count());
        $this->assertSame($periods, StudentProgram::query()->count());
    }

    // ------------------------------------------------------------- helpers ---

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeStudent(array $attributes = [], ?Program $program = null): Student
    {
        return Student::factory()->inProgram($program ?? $this->program)->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function profilePayload(array $overrides = []): array
    {
        return [
            'student_number' => 'STU-'.fake()->unique()->numerify('######'),
            'first_name' => 'Test',
            'last_name' => 'Student',
            'gender' => 'female',
            'date_of_birth' => '2005-05-05',
            'enrollment_date' => '2025-09-01',
            'program_id' => $this->program->id,
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
            'email' => 'stu'.fake()->unique()->numerify('####').'@test.test',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
            ...$overrides,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function updatePayload(Student $student, array $overrides = []): array
    {
        return [
            'student_number' => $student->student_number,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            ...$overrides,
        ];
    }

    private function insertEnrollment(Student $student, string $status): void
    {
        $courseId = DB::table('courses')->insertGetId([
            'department_id' => $this->program->department_id, 'code' => 'ENR'.fake()->unique()->numerify('###'), 'name' => 'Course', 'credits' => 3,
        ]);
        $semester = Semester::factory()->create();
        $offeringId = DB::table('course_offerings')->insertGetId(['course_id' => $courseId, 'semester_id' => $semester->id]);
        $sectionId = DB::table('sections')->insertGetId(['course_offering_id' => $offeringId, 'code' => 'A']);

        DB::table('enrollments')->insert([
            'student_id' => $student->id,
            'section_id' => $sectionId,
            'academic_year_id' => $semester->academic_year_id,
            'semester_id' => $semester->id,
            'status' => $status,
        ]);
    }

    private function userWithRole(string $slug, ?string $email = null): User
    {
        return User::factory()->create([
            'email' => $email ?? fake()->unique()->safeEmail(),
            'role_id' => RoleModel::query()->where('slug', $slug)->value('id')
                ?? RoleModel::factory()->withSlug($slug)->create()->id,
        ]);
    }
}
