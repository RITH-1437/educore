<?php

namespace Tests\Feature\FacultyDepartment;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Faculty;
use App\Models\Grade;
use App\Models\Lecturer;
use App\Models\Program;
use App\Models\Role as RoleModel;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Faculty / Department Admin unit scoping (`docs/32_Faculty-Admin-Scoping-Report.md`):
 * a Faculty Admin reads only their faculty's records, an unassigned one
 * reads none, and only a Faculty Admin can hold a faculty.
 */
class FacultyAdminScopingTest extends TestCase
{
    use RefreshDatabase;

    private Faculty $mine;

    private Faculty $theirs;

    private User $admin;

    private User $facultyAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->superAdmin()->create();
        $this->mine = Faculty::factory()->create(['name' => 'Faculty of Engineering']);
        $this->theirs = Faculty::factory()->create(['name' => 'Faculty of Law']);
        $this->facultyAdmin = $this->facultyAdminFor($this->mine);
    }

    public function test_super_admin_assigns_a_faculty_only_to_faculty_admins(): void
    {
        $facultyRole = RoleModel::query()->firstWhere('slug', Role::FacultyAdmin->value);
        $lecturerRole = RoleModel::factory()->withSlug(Role::Lecturer->value)->create();
        $payload = ['name' => 'Dara Unit', 'email' => 'dara.unit@educore.kh', 'role_id' => $facultyRole->id, 'faculty_id' => $this->mine->id, 'password' => 'secret-password', 'password_confirmation' => 'secret-password'];

        $id = $this->actingAs($this->admin)->postJson('/api/users', $payload)->assertCreated()
            ->assertJsonPath('data.faculty_id', $this->mine->id)->assertJsonPath('data.faculty', 'Faculty of Engineering')->json('data.id');
        $this->assertSame($this->mine->id, AuditLog::query()->where('action', 'user.created')->latest('id')->first()->after_values['faculty_id']);

        // Another role cannot hold a faculty.
        $this->actingAs($this->admin)->postJson('/api/users', [...$payload, 'email' => 'x@educore.kh', 'role_id' => $lecturerRole->id])->assertJsonValidationErrors('faculty_id');
        $this->actingAs($this->admin)->postJson('/api/users', [...$payload, 'email' => 'y@educore.kh', 'faculty_id' => 999999])->assertJsonValidationErrors('faculty_id');

        // Changing the role away from Faculty Admin clears it.
        $this->actingAs($this->admin)->putJson("/api/users/{$id}", ['name' => 'Dara Unit', 'email' => 'dara.unit@educore.kh', 'role_id' => $lecturerRole->id])
            ->assertOk()->assertJsonPath('data.faculty_id', null);

        // Web form gets the faculty options.
        $this->actingAs($this->admin)->get("/users/{$id}/edit")->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Users/Edit')->has('faculties', 2));
        $this->actingAs($this->facultyAdmin)->getJson('/api/users')->assertForbidden();
    }

    public function test_structure_and_people_lists_and_records_are_scoped(): void
    {
        [$myCourse, $theirCourse] = [$this->course($this->mine), $this->course($this->theirs)];
        $myLecturer = Lecturer::factory()->create(['department_id' => $myCourse->department_id]);
        $theirLecturer = Lecturer::factory()->create(['department_id' => $theirCourse->department_id]);
        $myStudent = Student::factory()->inProgram(Program::factory()->create(['department_id' => $myCourse->department_id]))->create();
        $theirStudent = Student::factory()->inProgram(Program::factory()->create(['department_id' => $theirCourse->department_id]))->create();

        $as = $this->actingAs($this->facultyAdmin);
        $as->getJson('/api/faculties')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $this->mine->id);
        $as->getJson("/api/faculties/{$this->theirs->id}")->assertForbidden();
        $as->getJson('/api/faculties-tree')->assertOk()->assertJsonCount(1, 'data');
        $as->getJson('/api/departments')->assertOk()->assertJsonCount(1, 'data');
        $as->getJson("/api/departments/{$theirCourse->department_id}")->assertForbidden();
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
        $as->get('/students')->assertOk()->assertInertia(fn (Assert $page) => $page->has('students.data', 1)->has('faculties.data', 1));
        $as->get('/courses')->assertOk()->assertInertia(fn (Assert $page) => $page->has('courses.data', 1)->has('departments.data', 1));
        $as->get('/faculties')->assertOk()->assertInertia(fn (Assert $page) => $page->has('faculties.data', 1));

        // Managers are unaffected.
        $this->actingAs($this->admin)->getJson('/api/courses')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_academic_activity_is_scoped_by_the_course_faculty(): void
    {
        $mySection = $this->section($this->mine);
        $theirSection = $this->section($this->theirs);
        $student = Student::factory()->create();
        Enrollment::factory()->create(['student_id' => $student->id, 'section_id' => $mySection->id]);
        $foreign = Enrollment::factory()->create(['section_id' => $theirSection->id]);
        Grade::query()->create(['enrollment_id' => $foreign->id, 'letter_grade' => 'A', 'grade_point' => 4, 'total_score' => 90, 'status' => 'submitted']);

        $as = $this->actingAs($this->facultyAdmin);
        $as->getJson('/api/offerings')->assertOk()->assertJsonCount(1, 'data');
        $as->getJson("/api/sections/{$theirSection->id}")->assertForbidden();
        $as->getJson("/api/sections/{$theirSection->id}/schedule")->assertForbidden();
        $as->getJson("/api/sections/{$mySection->id}/grades")->assertOk();
        $as->getJson("/api/sections/{$theirSection->id}/grades")->assertForbidden();
        $as->getJson('/api/enrollments')->assertOk()->assertJsonCount(1, 'data');
        $as->getJson("/api/enrollments/{$foreign->id}")->assertForbidden();
        // The approvals queue shows only their faculty's sections.
        $as->get('/grades')->assertOk()->assertInertia(fn (Assert $page) => $page->has('sections.data', 0));
        $this->actingAs($this->admin)->get('/grades')->assertInertia(fn (Assert $page) => $page->has('sections.data', 1));
    }

    public function test_unassigned_faculty_admin_sees_nothing(): void
    {
        $nobody = $this->facultyAdminFor(null);
        $course = $this->course($this->mine);
        $student = Student::factory()->inProgram(Program::factory()->create(['department_id' => $course->department_id]))->create();

        $this->actingAs($nobody)->getJson('/api/faculties')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($nobody)->getJson('/api/courses')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($nobody)->getJson('/api/students')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($nobody)->getJson("/api/courses/{$course->id}")->assertForbidden();
        $this->actingAs($nobody)->getJson("/api/students/{$student->id}")->assertForbidden();
        // Shared reference data stays readable.
        $this->actingAs($nobody)->getJson('/api/rooms')->assertOk();
        $this->actingAs($nobody)->getJson('/api/universities')->assertOk();

        $this->actingAs($nobody)->get('/dashboard')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('description', fn ($text) => str_contains($text, 'No faculty is assigned')));
        $this->actingAs($this->facultyAdmin)->get('/dashboard')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('description', fn ($text) => str_contains($text, 'Faculty of Engineering')));
    }

    public function test_deleting_a_faculty_unassigns_its_admins(): void
    {
        $empty = Faculty::factory()->create();
        $admin = $this->facultyAdminFor($empty);

        $empty->forceDelete();

        $this->assertNull($admin->fresh()->faculty_id);
        $this->assertSame(0, $admin->fresh()->facultyScope());
    }

    private function course(Faculty $faculty): Course
    {
        return Course::factory()->create(['department_id' => Department::factory()->create(['faculty_id' => $faculty->id])->id]);
    }

    private function section(Faculty $faculty): Section
    {
        return Section::factory()->create(['course_offering_id' => CourseOffering::factory()->create(['course_id' => $this->course($faculty)->id])->id]);
    }
}
