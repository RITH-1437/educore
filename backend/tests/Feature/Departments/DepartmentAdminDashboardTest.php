<?php

namespace Tests\Feature\Departments;

use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Enrollment;
use App\Models\Internship;
use App\Models\InternshipCompany;
use App\Models\Lecturer;
use App\Models\Role as RoleModel;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Department dashboard (`docs/39_Department-Only-Structure-Report.md`, formerly
 * the department dashboard of report 34): a Department Admin's /dashboard counts
 * the requests waiting for them and their department's headline numbers, never
 * another department's; the API serves any department to managers and only
 * their own to a Department Admin.
 */
class DepartmentAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Department $mine;

    private Department $theirs;

    private User $departmentAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mine = Department::factory()->create(['name' => 'Department of Engineering']);
        $this->theirs = Department::factory()->create(['name' => 'Department of Law']);
        $this->departmentAdmin = $this->departmentAdminFor($this->mine);
    }

    public function test_dashboard_counts_only_their_department(): void
    {
        $semester = Semester::factory()->open()->create();
        $ownSection = $this->section($this->mine, $semester, 'open');
        $foreignSection = $this->section($this->theirs, $semester, 'open');
        $this->section($this->mine, $semester, 'draft'); // not running: not counted

        // Students: two active and one suspended in the department, one elsewhere.
        [$enrolled, $idle] = [$this->student($this->mine), $this->student($this->mine)];
        $this->student($this->mine, Student::STATUS_SUSPENDED);
        $outsider = $this->student($this->theirs);

        // Our student counts once though enrolled twice; the outsider in our section is theirs.
        Enrollment::factory()->create(['student_id' => $enrolled->id, 'section_id' => $ownSection->id]);
        Enrollment::factory()->create(['student_id' => $enrolled->id, 'section_id' => $foreignSection->id]);
        Enrollment::factory()->create(['student_id' => $outsider->id, 'section_id' => $ownSection->id]);

        // Lecturers: one active and one inactive in the department, one elsewhere.
        Lecturer::factory()->create(['department_id' => $ownSection->offering->course->department_id]);
        Lecturer::factory()->inactive()->create(['department_id' => $ownSection->offering->course->department_id]);
        Lecturer::factory()->create(['department_id' => $foreignSection->offering->course->department_id]);

        // Waiting work: generated requests and draft internships are not waiting.
        $this->seed(DocumentTypeSeeder::class);
        foreach ([[$enrolled, 'pending'], [$idle, 'pending'], [$idle, 'approved'], [$idle, 'generated'], [$outsider, 'pending']] as [$student, $status]) {
            DocumentRequest::query()->create(['student_id' => $student->id, 'document_type_id' => DocumentType::query()->value('id'), 'status' => $status]);
        }
        $company = InternshipCompany::query()->create(['name' => 'Smart Axiata', 'industry' => 'Telecom', 'is_active' => true]);
        foreach ([[$enrolled, 'submitted'], [$idle, 'under_review'], [$idle, 'draft'], [$outsider, 'submitted']] as [$student, $status]) {
            Internship::query()->create(['student_id' => $student->id, 'company_id' => $company->id, 'position_title' => 'Software Intern', 'status' => $status]);
        }

        $waiting = ['document_requests_pending' => 2, 'document_requests_approved' => 1, 'internships_submitted' => 1, 'internships_under_review' => 1];
        $overview = ['students_active' => 2, 'lecturers_active' => 1, 'sections' => 1, 'students_enrolled' => 1];

        $this->actingAs($this->departmentAdmin)->get('/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('DepartmentAdmin/Dashboard')
            ->where('dashboard.department.name', 'Department of Engineering')
            ->where('dashboard.semester.id', $semester->id)
            ->where('dashboard.waiting', $waiting)
            ->where('dashboard.overview', $overview));

        // The API answers the same for their department and refuses another one.
        $this->actingAs($this->departmentAdmin)->getJson("/api/departments/{$this->mine->id}/dashboard")->assertOk()
            ->assertJsonPath('data.waiting', $waiting)->assertJsonPath('data.overview', $overview);
        $this->actingAs($this->departmentAdmin)->getJson("/api/departments/{$this->theirs->id}/dashboard")->assertForbidden();

        // Managers read any department; the outsider is the other department's enrolled student.
        $this->actingAs(User::factory()->superAdmin()->create())->getJson("/api/departments/{$this->theirs->id}/dashboard")->assertOk()
            ->assertJsonPath('data.waiting', ['document_requests_pending' => 1, 'document_requests_approved' => 0, 'internships_submitted' => 1, 'internships_under_review' => 0])
            ->assertJsonPath('data.overview', ['students_active' => 1, 'lecturers_active' => 1, 'sections' => 1, 'students_enrolled' => 1]);
    }

    public function test_access_unassigned_admins_and_no_semester(): void
    {
        $this->getJson("/api/departments/{$this->mine->id}/dashboard")->assertUnauthorized();

        // Without a semester the semester figures are null; the rest still counts.
        $this->student($this->mine);
        $this->actingAs($this->departmentAdmin)->get('/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('DepartmentAdmin/Dashboard')
            ->where('dashboard.semester', null)
            ->where('dashboard.overview', ['students_active' => 1, 'lecturers_active' => 0, 'sections' => null, 'students_enrolled' => null]));

        // An unassigned Department Admin gets no unit data.
        $nobody = $this->departmentAdminFor(null);
        $this->actingAs($nobody)->get('/dashboard')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('DepartmentAdmin/Dashboard')->where('dashboard', null));
        $this->actingAs($nobody)->getJson("/api/departments/{$this->mine->id}/dashboard")->assertForbidden();

        // Lecturers and students cannot read it; a University Admin can and keeps their own page.
        foreach (['lecturer', 'student'] as $role) {
            $this->actingAs($this->userWithRole($role))->getJson("/api/departments/{$this->mine->id}/dashboard")->assertForbidden();
        }
        $universityAdmin = $this->userWithRole('university-admin');
        $this->actingAs($universityAdmin)->getJson("/api/departments/{$this->mine->id}/dashboard")->assertOk()
            ->assertJsonPath('data.department.name', 'Department of Engineering');
        $this->actingAs($universityAdmin)->get('/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page->component('UniversityAdmin/Dashboard'));
    }

    private function section(Department $department, Semester $semester, string $status): Section
    {
        $course = Course::factory()->create(['department_id' => $department->id]);
        $offering = CourseOffering::factory()->create(['course_id' => $course->id, 'semester_id' => $semester->id]);

        return Section::factory()->create(['course_offering_id' => $offering->id, 'status' => $status]);
    }

    private function student(Department $department, string $status = Student::STATUS_ACTIVE): Student
    {
        $student = Student::factory()->withStatus($status)->create();
        $this->placeInDepartment($student, $department);

        return $student;
    }

    private function userWithRole(string $slug): User
    {
        $role = RoleModel::query()->firstWhere('slug', $slug) ?? RoleModel::factory()->withSlug($slug)->create();

        return User::factory()->create(['role_id' => $role->id]);
    }
}
