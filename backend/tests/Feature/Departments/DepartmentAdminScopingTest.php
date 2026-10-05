<?php

namespace Tests\Feature\Departments;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Lecturer;
use App\Models\Program;
use App\Models\Role as RoleModel;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Department Admin unit scoping (`docs/39_Department-Only-Structure-Report.md`,
 * formerly faculty scoping in report 32): a Department Admin reads only their
 * department's records, an unassigned one reads none, and only a Department
 * Admin can hold a department.
 */
class DepartmentAdminScopingTest extends TestCase
{
    use RefreshDatabase;

    private Department $mine;

    private Department $theirs;

    private User $admin;

    private User $departmentAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->superAdmin()->create();
        $this->mine = Department::factory()->create(['name' => 'Department of Engineering']);
        $this->theirs = Department::factory()->create(['name' => 'Department of Law']);
        $this->departmentAdmin = $this->departmentAdminFor($this->mine);
    }

    public function test_super_admin_assigns_a_department_only_to_department_admins(): void
    {
        $unitRole = RoleModel::query()->firstWhere('slug', Role::DepartmentAdmin->value);
        $lecturerRole = RoleModel::factory()->withSlug(Role::Lecturer->value)->create();
        $payload = ['name' => 'Dara Unit', 'email' => 'dara.unit@educore.kh', 'role_id' => $unitRole->id, 'department_id' => $this->mine->id, 'password' => 'secret-password', 'password_confirmation' => 'secret-password'];

        $id = $this->actingAs($this->admin)->postJson('/api/users', $payload)->assertCreated()
            ->assertJsonPath('data.department_id', $this->mine->id)->assertJsonPath('data.department', 'Department of Engineering')->json('data.id');
        $this->assertSame($this->mine->id, AuditLog::query()->where('action', 'user.created')->latest('id')->first()->after_values['department_id']);

        // Another role cannot hold a department.
        $this->actingAs($this->admin)->postJson('/api/users', [...$payload, 'email' => 'x@educore.kh', 'role_id' => $lecturerRole->id])->assertJsonValidationErrors('department_id');
        $this->actingAs($this->admin)->postJson('/api/users', [...$payload, 'email' => 'y@educore.kh', 'department_id' => 999999])->assertJsonValidationErrors('department_id');

        // Changing the role away from Department Admin clears it.
        $this->actingAs($this->admin)->putJson("/api/users/{$id}", ['name' => 'Dara Unit', 'email' => 'dara.unit@educore.kh', 'role_id' => $lecturerRole->id])
            ->assertOk()->assertJsonPath('data.department_id', null);

        // Web form gets the department options.
        $this->actingAs($this->admin)->get("/users/{$id}/edit")->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Users/Edit')->has('departments', 2));
        $this->actingAs($this->departmentAdmin)->getJson('/api/users')->assertForbidden();
    }

    /**
     * The Users screens post `department_id` for the `department-admin` role
     * (they still posted `faculty_id` for `faculty-admin` after report 39, so
     * saving a Department Admin from the edit screen cleared the department).
     */
    public function test_web_user_screens_keep_a_department_admins_department(): void
    {
        $unitRole = RoleModel::query()->firstWhere('slug', Role::DepartmentAdmin->value);

        $this->actingAs($this->admin)->get('/users/create')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Users/Create')->has('departments', 2)
                ->where('roles', fn ($roles) => collect($roles)->contains('slug', Role::DepartmentAdmin->value)));

        $this->actingAs($this->admin)->post('/users', ['name' => 'Sokha Unit', 'email' => 'sokha.unit@educore.kh', 'role_id' => $unitRole->id, 'department_id' => $this->mine->id, 'is_active' => true, 'password' => 'secret-password', 'password_confirmation' => 'secret-password'])
            ->assertRedirect('/users');
        $user = User::query()->firstWhere('email', 'sokha.unit@educore.kh');
        $this->assertSame($this->mine->id, $user->department_id);

        $this->actingAs($this->admin)->get("/users/{$user->id}/edit")->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('user.department_id', $this->mine->id)->where('user.role.slug', Role::DepartmentAdmin->value));

        // Saving the edit screen with the department moves it, it does not drop it.
        $this->actingAs($this->admin)->put("/users/{$user->id}", ['name' => 'Sokha Unit', 'email' => 'sokha.unit@educore.kh', 'role_id' => $unitRole->id, 'department_id' => $this->theirs->id, 'is_active' => true])
            ->assertRedirect();
        $this->assertSame($this->theirs->id, $user->fresh()->department_id);
    }

    public function test_people_and_catalog_lists_filter_by_department(): void
    {
        $myStudent = Student::factory()->inProgram(Program::factory()->create(['department_id' => $this->mine->id]))->create();
        Student::factory()->inProgram(Program::factory()->create(['department_id' => $this->theirs->id]))->create();
        $this->course($this->mine);
        $this->course($this->theirs);

        $this->actingAs($this->admin)->get("/students?filters[department_id]={$this->mine->id}")->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('filters.department_id', $this->mine->id)
                ->has('students.data', 1)->where('students.data.0.id', $myStudent->id)->has('departments.data', 2));
        $this->actingAs($this->admin)->get("/courses?filters[department_id]={$this->mine->id}")->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('filters.department_id', $this->mine->id)->has('courses.data', 1));

        // The Super Admin dashboard counts departments (it pointed at the removed faculties).
        $this->actingAs($this->admin)->get('/admin/dashboard')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('stats.total_departments', 2));
    }

    public function test_structure_and_people_lists_and_records_are_scoped(): void
    {
        [$myCourse, $theirCourse] = [$this->course($this->mine), $this->course($this->theirs)];
        $myLecturer = Lecturer::factory()->create(['department_id' => $this->mine->id]);
        $theirLecturer = Lecturer::factory()->create(['department_id' => $this->theirs->id]);
        $myStudent = Student::factory()->inProgram(Program::factory()->create(['department_id' => $this->mine->id]))->create();
        $theirStudent = Student::factory()->inProgram(Program::factory()->create(['department_id' => $this->theirs->id]))->create();

        $as = $this->actingAs($this->departmentAdmin);
        $as->getJson('/api/departments')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $this->mine->id);
        $as->getJson("/api/departments/{$this->theirs->id}")->assertForbidden();
        $as->getJson("/api/departments/{$this->theirs->id}/dashboard")->assertForbidden();
        $as->getJson('/api/programs')->assertOk()->assertJsonCount(1, 'data');
        $as->getJson('/api/courses')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $myCourse->id);
        $as->getJson("/api/courses/{$theirCourse->id}/grading-config")->assertForbidden();
        $as->getJson("/api/courses/{$myCourse->id}/grading-config")->assertOk();
        $as->getJson('/api/lecturers')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $myLecturer->id);
        $as->getJson("/api/lecturers/{$theirLecturer->id}")->assertForbidden();
        $as->getJson("/api/timetable/lecturer/{$theirLecturer->id}")->assertForbidden();
        $as->getJson('/api/students')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $myStudent->id);
        $as->getJson("/api/students/{$theirStudent->id}")->assertForbidden();
        $as->getJson("/api/students/{$theirStudent->id}/grades")->assertForbidden();
        $as->getJson("/api/students/{$myStudent->id}/grades")->assertOk();

        // Web lists and their filter options.
        $as->get('/students')->assertOk()->assertInertia(fn (Assert $page) => $page->has('students.data', 1)->has('departments.data', 1));
        $as->get('/courses')->assertOk()->assertInertia(fn (Assert $page) => $page->has('courses.data', 1)->has('departments.data', 1));
        $as->get('/departments')->assertOk()->assertInertia(fn (Assert $page) => $page->has('departments.data', 1));

        // Managers are unaffected.
        $this->actingAs($this->admin)->getJson('/api/courses')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_academic_activity_is_scoped_by_the_course_department(): void
    {
        $mySection = $this->section($this->mine);
        $theirSection = $this->section($this->theirs);
        $student = Student::factory()->create();
        Enrollment::factory()->create(['student_id' => $student->id, 'section_id' => $mySection->id]);
        $foreign = Enrollment::factory()->create(['section_id' => $theirSection->id]);
        Grade::query()->create(['enrollment_id' => $foreign->id, 'letter_grade' => 'A', 'grade_point' => 4, 'total_score' => 90, 'status' => 'submitted']);

        $as = $this->actingAs($this->departmentAdmin);
        $as->getJson('/api/offerings')->assertOk()->assertJsonCount(1, 'data');
        $as->getJson("/api/sections/{$theirSection->id}")->assertForbidden();
        $as->getJson("/api/sections/{$theirSection->id}/schedule")->assertForbidden();
        $as->getJson("/api/sections/{$mySection->id}/grades")->assertOk();
        $as->getJson("/api/sections/{$theirSection->id}/grades")->assertForbidden();
        $as->getJson('/api/enrollments')->assertOk()->assertJsonCount(1, 'data');
        $as->getJson("/api/enrollments/{$foreign->id}")->assertForbidden();
        // The approvals queue shows only their department's sections.
        $as->get('/grades')->assertOk()->assertInertia(fn (Assert $page) => $page->has('sections.data', 0));
        $this->actingAs($this->admin)->get('/grades')->assertInertia(fn (Assert $page) => $page->has('sections.data', 1));
    }

    public function test_manager_only_form_options_are_not_sent_to_department_admins(): void
    {
        $semester = Semester::factory()->create(['status' => 'open']);
        $offering = CourseOffering::factory()->create(['course_id' => $this->course($this->mine)->id, 'semester_id' => $semester->id, 'status' => 'open']);
        Section::factory()->create(['course_offering_id' => $offering->id, 'status' => 'open']);
        Student::factory()->create();
        Lecturer::factory()->create();
        // Accounts without a profile, offered as "link existing account" when creating one.
        foreach ([Role::Lecturer, Role::Student] as $role) {
            User::factory()->create(['role_id' => RoleModel::query()->firstOrCreate(['slug' => $role->value], ['name' => $role->name, 'is_system' => true])->id]);
        }

        // The pages still open for a Department Admin, without the university-wide option lists…
        $this->actingAs($this->departmentAdmin)->get('/enrollments')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('students', 0)->has('openSections', 0));
        $this->actingAs($this->departmentAdmin)->get("/offerings/{$offering->id}")->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('lecturers', 0));
        $this->actingAs($this->departmentAdmin)->get('/lecturers')->assertOk()->assertInertia(fn (Assert $page) => $page->has('unlinkedAccounts', 0));
        $this->actingAs($this->departmentAdmin)->get('/students')->assertOk()->assertInertia(fn (Assert $page) => $page->has('unlinkedAccounts', 0));

        // …which managers still receive for their forms.
        $this->actingAs($this->admin)->get('/enrollments')->assertInertia(fn (Assert $page) => $page->has('students', 1)->has('openSections', 1));
        $this->actingAs($this->admin)->get("/offerings/{$offering->id}")->assertInertia(fn (Assert $page) => $page->has('lecturers', 1));
        $this->actingAs($this->admin)->get('/lecturers')->assertInertia(fn (Assert $page) => $page->has('unlinkedAccounts', 1));
        $this->actingAs($this->admin)->get('/students')->assertInertia(fn (Assert $page) => $page->has('unlinkedAccounts', 1));
    }

    public function test_unassigned_department_admin_sees_nothing(): void
    {
        $nobody = $this->departmentAdminFor(null);
        $course = $this->course($this->mine);
        $student = Student::factory()->inProgram(Program::factory()->create(['department_id' => $this->mine->id]))->create();

        $this->actingAs($nobody)->getJson('/api/departments')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($nobody)->getJson('/api/courses')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($nobody)->getJson('/api/students')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($nobody)->getJson("/api/courses/{$course->id}")->assertForbidden();
        $this->actingAs($nobody)->getJson("/api/students/{$student->id}")->assertForbidden();
        // Shared reference data stays readable.
        $this->actingAs($nobody)->getJson('/api/rooms')->assertOk();
        $this->actingAs($nobody)->getJson('/api/universities')->assertOk();

        $this->actingAs($nobody)->get('/dashboard')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('DepartmentAdmin/Dashboard')->where('dashboard', null));
        $this->actingAs($this->departmentAdmin)->get('/dashboard')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('dashboard.department.name', 'Department of Engineering'));
    }

    public function test_deleting_a_department_unassigns_its_admins(): void
    {
        $empty = Department::factory()->create();
        $admin = $this->departmentAdminFor($empty);

        $empty->forceDelete();

        $this->assertNull($admin->fresh()->department_id);
        $this->assertSame(0, $admin->fresh()->departmentScope());
    }

    private function course(Department $department): Course
    {
        return Course::factory()->create(['department_id' => $department->id]);
    }

    private function section(Department $department): Section
    {
        return Section::factory()->create(['course_offering_id' => CourseOffering::factory()->create(['course_id' => $this->course($department)->id])->id]);
    }
}
