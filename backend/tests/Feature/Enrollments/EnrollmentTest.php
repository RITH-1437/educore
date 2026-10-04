<?php

namespace Tests\Feature\Enrollments;

use App\Enums\Role;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Role as RoleModel;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Services\EnrollmentService;
use Database\Seeders\AcademicYearSeeder;
use Database\Seeders\CourseOfferingSeeder;
use Database\Seeders\CourseSeeder;
use Database\Seeders\EnrollmentSeeder;
use Database\Seeders\LecturerSeeder;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\StudentSeeder;
use Database\Seeders\UniversityStructureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Module 9.9 — enrollment rules, self-service scoping, drop/complete, seeding.
 */
class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $departmentAdmin;

    private Semester $semester;

    private Section $section;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->superAdmin()->create();
        $this->departmentAdmin = User::factory()->create(['role_id' => RoleModel::factory()->withSlug(Role::DepartmentAdmin->value)->create()->id]);
        $this->semester = Semester::factory()->create(['status' => 'open']);
        $this->section = $this->openSection(Course::factory()->create(['code' => 'CS101', 'credits' => 3]), 2);
        $this->student = Student::factory()->create();
    }

    // ------------------------------------------------------------ happy path ---

    public function test_admin_enrolls_a_student(): void
    {
        $this->actingAs($this->admin)->postJson('/api/enrollments', ['student_id' => $this->student->id, 'section_id' => $this->section->id])
            ->assertCreated()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.section.course.code', 'CS101')
            ->assertJsonPath('data.semester_id', $this->semester->id);
    }

    public function test_student_enrolls_themself_and_cannot_name_another_student(): void
    {
        $other = Student::factory()->create();

        $this->actingAs($this->student->user)->postJson('/api/enrollments', ['section_id' => $this->section->id, 'student_id' => $other->id])
            ->assertUnprocessable()->assertJsonValidationErrors(['student_id']);

        $this->actingAs($this->student->user)->postJson('/api/enrollments', ['section_id' => $this->section->id])
            ->assertCreated()->assertJsonPath('data.student_id', $this->student->id);

        $this->assertDatabaseMissing('enrollments', ['student_id' => $other->id]);
    }

    public function test_scoping_of_reads(): void
    {
        $mine = $this->enroll($this->student);
        $other = Student::factory()->create();
        $theirs = $this->enroll($other);

        $this->actingAs($this->student->user)->getJson("/api/enrollments/{$mine->id}")->assertOk();
        $this->actingAs($this->student->user)->getJson("/api/enrollments/{$theirs->id}")->assertForbidden();
        $this->actingAs($this->student->user)->getJson('/api/enrollments')->assertForbidden();
        $this->actingAs($this->student->user)->getJson("/api/students/{$this->student->id}/enrollments")->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($this->student->user)->getJson("/api/students/{$other->id}/enrollments")->assertForbidden();
        $this->departmentAdmin->update(['department_id' => $this->departmentOfSection($this->section)]);
        $this->actingAs($this->departmentAdmin)->getJson('/api/enrollments')->assertOk()->assertJsonCount(2, 'data');
        $this->actingAs($this->departmentAdmin)->getJson("/api/enrollments/{$theirs->id}")->assertOk();
        $this->actingAs($this->departmentAdminFor(Department::factory()->create()))->getJson('/api/enrollments')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($this->departmentAdminFor(null))->getJson("/api/enrollments/{$mine->id}")->assertForbidden();
        $this->actingAs($this->departmentAdmin)->postJson('/api/enrollments', ['student_id' => $this->student->id, 'section_id' => $this->section->id])->assertForbidden();
    }

    // ----------------------------------------------------------------- rules ---

    public function test_inactive_student_and_closed_registration_are_refused(): void
    {
        $suspended = Student::factory()->create(['status' => 'suspended']);
        $this->post409($suspended, $this->section);

        $this->semester->update(['status' => 'closed']);
        $this->post409($this->student, $this->section);
        $this->semester->update(['status' => 'open', 'enrollment_end' => now()->subDay()->toDateString()]);
        $this->post409($this->student, $this->section);
        $this->semester->update(['enrollment_end' => null]);

        $this->section->offering->update(['status' => 'published']);
        $this->post409($this->student, $this->section);
        $this->section->offering->update(['status' => 'open']);

        $this->section->update(['status' => 'draft']);
        $this->post409($this->student, $this->section);
    }

    public function test_capacity_and_offering_maximum(): void
    {
        $this->enroll(Student::factory()->create());
        $this->enroll(Student::factory()->create());

        $this->post409($this->student, $this->section); // capacity 2 reached

        $section = $this->openSection(Course::factory()->create(), 5);
        $section->offering->update(['max_enrollments' => 1]);
        $this->enroll(Student::factory()->create(), $section);
        $this->post409($this->student, $section); // offering maximum reached
    }

    public function test_dropped_seat_is_freed(): void
    {
        $first = $this->enroll(Student::factory()->create());
        $this->enroll(Student::factory()->create());
        app(EnrollmentService::class)->drop($first);

        $this->actingAs($this->admin)->postJson('/api/enrollments', ['student_id' => $this->student->id, 'section_id' => $this->section->id])->assertCreated();
    }

    public function test_duplicate_and_second_section_of_same_offering_are_refused(): void
    {
        $this->enroll($this->student);
        $sectionB = Section::factory()->create(['course_offering_id' => $this->section->course_offering_id, 'code' => 'B', 'status' => 'open', 'capacity' => 10]);

        $this->actingAs($this->admin)->postJson('/api/enrollments', ['student_id' => $this->student->id, 'section_id' => $this->section->id])
            ->assertUnprocessable()->assertJsonValidationErrors(['section_id']);
        $this->actingAs($this->admin)->postJson('/api/enrollments', ['student_id' => $this->student->id, 'section_id' => $sectionB->id])
            ->assertUnprocessable()->assertJsonValidationErrors(['section_id']);
    }

    public function test_re_enrolling_after_a_drop_revives_the_row(): void
    {
        $enrollment = $this->enroll($this->student);
        app(EnrollmentService::class)->drop($enrollment);

        $this->actingAs($this->admin)->postJson('/api/enrollments', ['student_id' => $this->student->id, 'section_id' => $this->section->id])
            ->assertCreated()->assertJsonPath('data.id', $enrollment->id)->assertJsonPath('data.status', 'confirmed');
        $this->assertSame(1, Enrollment::query()->count());
    }

    public function test_strict_prerequisites_must_be_passed(): void
    {
        $advanced = Course::factory()->create(['code' => 'CS201']);
        $advanced->prerequisites()->attach($this->section->offering->course_id, ['is_strict' => true]);
        $optional = Course::factory()->create(['code' => 'OPT1']);
        $advanced->prerequisites()->attach($optional->id, ['is_strict' => false]);
        $advancedSection = $this->openSection($advanced, 10);

        $this->actingAs($this->admin)->postJson('/api/enrollments', ['student_id' => $this->student->id, 'section_id' => $advancedSection->id])
            ->assertUnprocessable()->assertJsonValidationErrors(['section_id' => 'Prerequisites not met: CS101.']);

        // A failed grade does not count as passed…
        $done = $this->enroll($this->student);
        $done->update(['status' => 'completed']);
        DB::table('grades')->insert(['enrollment_id' => $done->id, 'letter_grade' => 'F', 'grade_point' => 0, 'status' => 'finalized']);
        $this->actingAs($this->admin)->postJson('/api/enrollments', ['student_id' => $this->student->id, 'section_id' => $advancedSection->id])
            ->assertUnprocessable();

        // …a passing one does (non-strict prerequisites are not required).
        DB::table('grades')->where('enrollment_id', $done->id)->update(['letter_grade' => 'B', 'grade_point' => 3]);
        $this->actingAs($this->admin)->postJson('/api/enrollments', ['student_id' => $this->student->id, 'section_id' => $advancedSection->id])
            ->assertCreated();
    }

    public function test_semester_credit_limit(): void
    {
        config(['academics.max_semester_credits' => 6]);
        $this->enroll($this->student, $this->openSection(Course::factory()->create(['credits' => 4]), 10));

        $this->actingAs($this->admin)->postJson('/api/enrollments', ['student_id' => $this->student->id, 'section_id' => $this->section->id])
            ->assertUnprocessable()->assertJsonValidationErrors(['section_id']);

        $this->actingAs($this->admin)->postJson('/api/enrollments', ['student_id' => $this->student->id, 'section_id' => $this->openSection(Course::factory()->create(['credits' => 2]), 10)->id])
            ->assertCreated();
    }

    // ------------------------------------------------------- drop / complete ---

    public function test_drop_keeps_history_and_becomes_withdrawn_with_records(): void
    {
        $plain = $this->enroll($this->student);
        $this->actingAs($this->student->user)->deleteJson("/api/enrollments/{$plain->id}")
            ->assertOk()->assertJsonPath('data.status', 'dropped');
        $this->assertDatabaseHas('enrollments', ['id' => $plain->id, 'deleted_at' => null]);

        $other = Student::factory()->create();
        $graded = $this->enroll($other);
        DB::table('grades')->insert(['enrollment_id' => $graded->id, 'status' => 'draft']);
        $this->actingAs($this->admin)->deleteJson("/api/enrollments/{$graded->id}")
            ->assertOk()->assertJsonPath('data.status', 'withdrawn');

        $this->actingAs($this->admin)->deleteJson("/api/enrollments/{$graded->id}")->assertStatus(409);
        $this->actingAs($this->student->user)->deleteJson("/api/enrollments/{$graded->id}")->assertForbidden();
    }

    public function test_complete_is_admin_only_and_requires_confirmed(): void
    {
        $enrollment = $this->enroll($this->student);

        $this->actingAs($this->student->user)->postJson("/api/enrollments/{$enrollment->id}/complete")->assertForbidden();
        $this->actingAs($this->admin)->postJson("/api/enrollments/{$enrollment->id}/complete")->assertOk()->assertJsonPath('data.status', 'completed');
        $this->actingAs($this->admin)->postJson("/api/enrollments/{$enrollment->id}/complete")->assertStatus(409);
    }

    // ------------------------------------------------------------ web / seed ---

    public function test_web_screens_and_self_service_flow(): void
    {
        $this->actingAs($this->admin)->get('/enrollments')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Enrollments/Index')->has('openSections', 1)->where('openSections.0.seats', 2));

        $this->actingAs($this->student->user)->get('/registration')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Registration/Index')->has('sections', 1)->where('sections.0.registration_open', true));

        $this->actingAs($this->student->user)->post('/registration', ['section_id' => $this->section->id])->assertSessionHas('success');
        $enrollment = Enrollment::query()->firstOrFail();
        $this->actingAs($this->student->user)->post("/registration/{$enrollment->id}/drop")->assertSessionHas('success');
        $this->assertSame('dropped', $enrollment->refresh()->status);

        $this->actingAs($this->admin)->get('/registration')->assertForbidden();
        $this->actingAs($this->student->user)->get('/enrollments')->assertForbidden();
    }

    public function test_seeder_obeys_the_rules_and_is_idempotent(): void
    {
        foreach ([UniversityStructureSeeder::class, ProgramSeeder::class, CourseSeeder::class, LecturerSeeder::class, StudentSeeder::class, AcademicYearSeeder::class, CourseOfferingSeeder::class] as $seeder) {
            $this->seed($seeder);
        }

        $this->seed(EnrollmentSeeder::class);
        $count = Enrollment::query()->count();
        $this->seed(EnrollmentSeeder::class);

        $this->assertGreaterThan(0, $count);
        $this->assertSame($count, Enrollment::query()->count());
    }

    // ------------------------------------------------------------- helpers ---

    private function openSection(Course $course, int $capacity): Section
    {
        $offering = CourseOffering::factory()->create(['course_id' => $course->id, 'semester_id' => $this->semester->id, 'status' => 'open']);

        return Section::factory()->create(['course_offering_id' => $offering->id, 'code' => 'A', 'status' => 'open', 'capacity' => $capacity]);
    }

    private function enroll(Student $student, ?Section $section = null): Enrollment
    {
        return app(EnrollmentService::class)->enroll($student, $section ?? $this->section);
    }

    private function post409(Student $student, Section $section): void
    {
        $this->actingAs($this->admin)->postJson('/api/enrollments', ['student_id' => $student->id, 'section_id' => $section->id])->assertStatus(409);
    }
}
