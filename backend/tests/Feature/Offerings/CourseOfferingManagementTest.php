<?php

namespace Tests\Feature\Offerings;

use App\Enums\Role;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\Role as RoleModel;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\AcademicYearSeeder;
use Database\Seeders\CourseOfferingSeeder;
use Database\Seeders\CourseSeeder;
use Database\Seeders\LecturerSeeder;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\UniversityStructureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Module 9.8 — course offerings, sections and lecturer assignment.
 */
class CourseOfferingManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $facultyAdmin;

    private User $student;

    private Course $course;

    private Semester $semester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->facultyAdmin = $this->userWithRole(Role::FacultyAdmin->value);
        $this->student = $this->userWithRole(Role::Student->value);
        $this->course = Course::factory()->create(['department_id' => Department::factory()->create()->id, 'code' => 'CS101']);
        $this->semester = Semester::factory()->create(['status' => 'open']);
    }

    // ---------------------------------------------------------------- auth ---

    public function test_roles_are_enforced(): void
    {
        $offering = $this->offering();

        $this->getJson('/api/offerings')->assertUnauthorized();
        $this->actingAs($this->student)->getJson('/api/offerings')->assertForbidden();
        $this->actingAs($this->student)->get('/offerings')->assertForbidden();

        $this->actingAs($this->facultyAdmin)->getJson('/api/offerings')->assertOk();
        $this->actingAs($this->facultyAdmin)->get("/offerings/{$offering->id}")->assertOk();
        $this->actingAs($this->facultyAdmin)->postJson('/api/offerings', $this->offeringPayload())->assertForbidden();
        $this->actingAs($this->facultyAdmin)->postJson("/api/offerings/{$offering->id}/sections", ['code' => 'A', 'capacity' => 30])->assertForbidden();
    }

    // ----------------------------------------------------------- offerings ---

    public function test_offering_crud_and_screens(): void
    {
        $response = $this->actingAs($this->superAdmin)->postJson('/api/offerings', $this->offeringPayload(['status' => 'published', 'max_enrollments' => 80]))
            ->assertCreated()
            ->assertJsonPath('data.course.code', 'CS101')
            ->assertJsonPath('data.semester.id', $this->semester->id)
            ->assertJsonPath('data.status', 'published');
        $id = $response->json('data.id');

        $this->actingAs($this->superAdmin)->patchJson("/api/offerings/{$id}", ['status' => 'open', 'max_enrollments' => 90])
            ->assertOk()->assertJsonPath('data.max_enrollments', 90);

        $this->actingAs($this->superAdmin)->get('/offerings')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Offerings/Index')->has('offerings.data', 1)->has('semesters', 1)->has('courses', 1));
        $this->actingAs($this->superAdmin)->get("/offerings/{$id}")->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Offerings/Show')->where('offering.id', $id)->has('offering.sections', 0));

        $this->actingAs($this->superAdmin)->deleteJson("/api/offerings/{$id}")->assertNoContent();
    }

    public function test_offering_rules(): void
    {
        $this->offering();

        // Duplicate course × semester.
        $this->actingAs($this->superAdmin)->postJson('/api/offerings', $this->offeringPayload())
            ->assertUnprocessable()->assertJsonValidationErrors(['semester_id']);

        // Only active courses.
        $draft = Course::factory()->draft()->create();
        $this->actingAs($this->superAdmin)->postJson('/api/offerings', $this->offeringPayload(['course_id' => $draft->id]))
            ->assertUnprocessable()->assertJsonValidationErrors(['course_id']);

        // Never in a completed semester.
        $completed = Semester::factory()->create(['status' => 'completed']);
        $this->actingAs($this->superAdmin)->postJson('/api/offerings', $this->offeringPayload(['semester_id' => $completed->id]))
            ->assertStatus(409);

        $this->actingAs($this->superAdmin)->postJson('/api/offerings', $this->offeringPayload(['status' => 'running']))
            ->assertUnprocessable()->assertJsonValidationErrors(['status']);
    }

    public function test_offering_with_sections_cannot_be_deleted_and_course_guard_sees_it(): void
    {
        $offering = $this->offering();
        Section::factory()->create(['course_offering_id' => $offering->id]);

        $this->actingAs($this->superAdmin)->deleteJson("/api/offerings/{$offering->id}")->assertStatus(409);
        $this->actingAs($this->superAdmin)->deleteJson("/api/courses/{$this->course->id}")->assertStatus(409);
    }

    public function test_list_filters(): void
    {
        $this->offering();
        $other = CourseOffering::factory()->create(['status' => 'closed']);

        $this->actingAs($this->superAdmin)->getJson("/api/offerings?filters[semester_id]={$this->semester->id}")->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($this->superAdmin)->getJson('/api/offerings?filters[status]=closed')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $other->id);
        $this->actingAs($this->superAdmin)->getJson('/api/offerings?search=CS101')->assertOk()->assertJsonCount(1, 'data');
    }

    // ------------------------------------------------------------- sections ---

    public function test_section_crud_and_rules(): void
    {
        $offering = $this->offering();

        $id = $this->actingAs($this->superAdmin)->postJson("/api/offerings/{$offering->id}/sections", ['code' => 'A', 'capacity' => 40])
            ->assertCreated()->assertJsonPath('data.code', 'A')->assertJsonPath('data.status', 'draft')->json('data.id');

        // Code unique within the offering; capacity ≥ 1; code format.
        $this->actingAs($this->superAdmin)->postJson("/api/offerings/{$offering->id}/sections", ['code' => 'A', 'capacity' => 0])
            ->assertUnprocessable()->assertJsonValidationErrors(['code', 'capacity']);
        $this->actingAs($this->superAdmin)->postJson("/api/offerings/{$offering->id}/sections", ['code' => 'A B', 'capacity' => 10])
            ->assertUnprocessable()->assertJsonValidationErrors(['code']);

        // Same code in another offering is fine.
        $this->actingAs($this->superAdmin)->postJson('/api/offerings/'.CourseOffering::factory()->create()->id.'/sections', ['code' => 'A', 'capacity' => 10])
            ->assertCreated();

        $this->actingAs($this->superAdmin)->putJson("/api/sections/{$id}", ['code' => 'A', 'capacity' => 45, 'status' => 'open', 'name' => 'Morning'])
            ->assertOk()->assertJsonPath('data.capacity', 45)->assertJsonPath('data.offering.course.code', 'CS101');

        $this->actingAs($this->superAdmin)->deleteJson("/api/sections/{$id}")->assertNoContent();
    }

    public function test_capacity_cannot_drop_below_open_enrollments_and_history_blocks_delete(): void
    {
        $section = Section::factory()->create(['course_offering_id' => $this->offering()->id, 'capacity' => 10]);
        $this->enroll($section, 'confirmed');
        $this->enroll($section, 'pending');

        $this->actingAs($this->superAdmin)->putJson("/api/sections/{$section->id}", ['code' => $section->code, 'capacity' => 1, 'status' => 'open'])
            ->assertUnprocessable()->assertJsonValidationErrors(['capacity']);
        $this->actingAs($this->superAdmin)->putJson("/api/sections/{$section->id}", ['code' => $section->code, 'capacity' => 2, 'status' => 'open'])
            ->assertOk();

        $this->actingAs($this->superAdmin)->deleteJson("/api/sections/{$section->id}")
            ->assertStatus(409)->assertJsonPath('message', 'This section has enrollments and cannot be deleted. Archive it instead.');
    }

    public function test_no_sections_in_a_completed_semester(): void
    {
        $offering = CourseOffering::factory()->create(['semester_id' => Semester::factory()->create(['status' => 'completed'])->id]);

        $this->actingAs($this->superAdmin)->postJson("/api/offerings/{$offering->id}/sections", ['code' => 'A', 'capacity' => 10])
            ->assertStatus(409);
    }

    // ------------------------------------------------------------ lecturers ---

    public function test_lecturer_assignment_rules(): void
    {
        $section = Section::factory()->create(['course_offering_id' => $this->offering()->id]);
        $primary = Lecturer::factory()->create();
        $assistant = Lecturer::factory()->create();
        $inactive = Lecturer::factory()->inactive()->create();

        $this->actingAs($this->superAdmin)->postJson("/api/sections/{$section->id}/lecturers", ['lecturer_id' => $primary->id])
            ->assertCreated()->assertJsonPath('data.lecturers.0.role', 'primary');

        // Duplicate, second primary, inactive lecturer.
        $this->actingAs($this->superAdmin)->postJson("/api/sections/{$section->id}/lecturers", ['lecturer_id' => $primary->id, 'role' => 'tutor'])
            ->assertUnprocessable()->assertJsonValidationErrors(['lecturer_id']);
        $this->actingAs($this->superAdmin)->postJson("/api/sections/{$section->id}/lecturers", ['lecturer_id' => $assistant->id, 'role' => 'primary'])
            ->assertUnprocessable()->assertJsonValidationErrors(['role']);
        $this->actingAs($this->superAdmin)->postJson("/api/sections/{$section->id}/lecturers", ['lecturer_id' => $inactive->id, 'role' => 'tutor'])
            ->assertUnprocessable()->assertJsonValidationErrors(['lecturer_id']);

        $this->actingAs($this->superAdmin)->postJson("/api/sections/{$section->id}/lecturers", ['lecturer_id' => $assistant->id, 'role' => 'assistant'])
            ->assertCreated()->assertJsonCount(2, 'data.lecturers');

        // The lecturer delete guard now applies.
        $this->actingAs($this->superAdmin)->deleteJson("/api/lecturers/{$primary->id}")->assertStatus(409);

        $this->actingAs($this->superAdmin)->deleteJson("/api/sections/{$section->id}/lecturers/{$primary->id}")->assertNoContent();
        $this->actingAs($this->superAdmin)->deleteJson("/api/sections/{$section->id}/lecturers/{$primary->id}")->assertNotFound();
    }

    public function test_lecturer_teaching_load_is_visible_to_staff_and_the_lecturer_only(): void
    {
        $section = Section::factory()->create(['course_offering_id' => $this->offering()->id, 'code' => 'B']);
        $lecturer = Lecturer::factory()->create();
        $other = Lecturer::factory()->create();
        $section->lecturers()->attach($lecturer->id, ['role' => 'primary']);

        $this->actingAs($this->superAdmin)->getJson("/api/lecturers/{$lecturer->id}/sections")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.code', 'B')->assertJsonPath('data.0.offering.course.code', 'CS101');
        $this->actingAs($lecturer->user)->getJson("/api/lecturers/{$lecturer->id}/sections")->assertOk();
        $this->actingAs($lecturer->user)->getJson("/api/lecturers/{$other->id}/sections")->assertForbidden();

        $this->actingAs($this->superAdmin)->get("/lecturers/{$lecturer->id}/edit")
            ->assertInertia(fn (Assert $page) => $page->component('Lecturers/Edit')->has('sections', 1));
    }

    public function test_web_section_and_assignment_flow(): void
    {
        $offering = $this->offering();
        $lecturer = Lecturer::factory()->create();

        $this->actingAs($this->superAdmin)->post("/offerings/{$offering->id}/sections", ['code' => 'C', 'capacity' => 20])->assertSessionHas('success');
        $section = Section::query()->where('code', 'C')->firstOrFail();
        $this->actingAs($this->superAdmin)->post("/sections/{$section->id}/lecturers", ['lecturer_id' => $lecturer->id, 'role' => 'primary'])->assertSessionHas('success');
        $this->actingAs($this->superAdmin)->put("/sections/{$section->id}", ['code' => 'C', 'capacity' => 25, 'status' => 'open'])->assertSessionHas('success');
        $this->actingAs($this->superAdmin)->delete("/sections/{$section->id}/lecturers/{$lecturer->id}")->assertSessionHas('success');
        $this->actingAs($this->superAdmin)->delete("/sections/{$section->id}")->assertSessionHas('success');
        $this->assertDatabaseCount('sections', 0);
    }

    public function test_seeder_is_idempotent(): void
    {
        foreach ([UniversityStructureSeeder::class, ProgramSeeder::class, CourseSeeder::class, LecturerSeeder::class, AcademicYearSeeder::class] as $seeder) {
            $this->seed($seeder);
        }
        $this->seed(CourseOfferingSeeder::class);
        $counts = [CourseOffering::query()->count(), Section::query()->count(), DB::table('section_lecturers')->count()];

        $this->seed(CourseOfferingSeeder::class);

        $this->assertGreaterThan(0, $counts[0]);
        $this->assertSame($counts, [CourseOffering::query()->count(), Section::query()->count(), DB::table('section_lecturers')->count()]);
    }

    // ------------------------------------------------------------- helpers ---

    private function offering(): CourseOffering
    {
        return CourseOffering::factory()->create(['course_id' => $this->course->id, 'semester_id' => $this->semester->id, 'status' => 'open']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function offeringPayload(array $overrides = []): array
    {
        return ['course_id' => $this->course->id, 'semester_id' => $this->semester->id, ...$overrides];
    }

    private function enroll(Section $section, string $status): void
    {
        $student = Student::factory()->create();
        $semester = $section->offering->semester;

        DB::table('enrollments')->insert([
            'student_id' => $student->id,
            'section_id' => $section->id,
            'academic_year_id' => $semester->academic_year_id,
            'semester_id' => $semester->id,
            'status' => $status,
        ]);
    }

    private function userWithRole(string $slug): User
    {
        return User::factory()->create([
            'role_id' => RoleModel::query()->where('slug', $slug)->value('id') ?? RoleModel::factory()->withSlug($slug)->create()->id,
        ]);
    }
}
