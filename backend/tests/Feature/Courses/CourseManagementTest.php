<?php

namespace Tests\Feature\Courses;

use App\Enums\Role;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Role as RoleModel;
use App\Models\Semester;
use App\Models\University;
use App\Models\User;
use Database\Seeders\CourseSeeder;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\UniversityStructureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Module 9.7 — Course catalog, prerequisites and program curriculum.
 *
 * Role scoping, CRUD over web and API, validation, lifecycle (archive vs
 * delete), prerequisite integrity (self, duplicate, archived, cycles) and the
 * program curriculum that module 9.5 deferred.
 */
class CourseManagementTest extends TestCase
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
        $this->getJson('/api/courses')->assertUnauthorized();
        $this->get('/courses')->assertRedirect('/login');
    }

    public function test_student_and_lecturer_are_forbidden(): void
    {
        foreach ([$this->student, $this->lecturer] as $user) {
            $this->actingAs($user)->get('/courses')->assertForbidden();
            $this->actingAs($user)->getJson('/api/courses')->assertForbidden();
            $this->actingAs($user)->postJson('/api/courses', $this->payload())->assertForbidden();
        }
    }

    public function test_faculty_admin_may_read_but_not_write(): void
    {
        $course = Course::factory()->create(['department_id' => $this->department->id]);
        $other = Course::factory()->create(['department_id' => $this->department->id]);
        $program = Program::factory()->create(['department_id' => $this->department->id]);

        $this->actingAs($this->facultyAdmin)->get('/courses')->assertOk();
        $this->actingAs($this->facultyAdmin)->getJson('/api/courses')->assertOk();
        $this->actingAs($this->facultyAdmin)->getJson("/api/courses/{$course->id}")->assertOk();

        $this->actingAs($this->facultyAdmin)->postJson('/api/courses', $this->payload())->assertForbidden();
        $this->actingAs($this->facultyAdmin)->get("/courses/{$course->id}/edit")->assertForbidden();
        $this->actingAs($this->facultyAdmin)->postJson("/api/courses/{$course->id}/archive")->assertForbidden();
        $this->actingAs($this->facultyAdmin)->deleteJson("/api/courses/{$course->id}")->assertForbidden();
        $this->actingAs($this->facultyAdmin)
            ->postJson("/api/courses/{$course->id}/prerequisites", ['prerequisite_course_id' => $other->id])
            ->assertForbidden();
        $this->actingAs($this->facultyAdmin)
            ->postJson("/api/programs/{$program->id}/courses", ['course_id' => $course->id])
            ->assertForbidden();

        $this->assertDatabaseCount('courses', 2);
        $this->assertDatabaseCount('course_programs', 0);
    }

    // ------------------------------------------------------------ screens ---

    public function test_index_screen_lists_courses_with_lookups(): void
    {
        Course::factory()->count(2)->create(['department_id' => $this->department->id]);
        Program::factory()->create(['department_id' => $this->department->id]);

        $this->actingAs($this->superAdmin)
            ->get('/courses')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Courses/Index')
                ->has('courses.data', 2)
                ->has('faculties.data', 1)
                ->has('departments.data', 1)
                ->has('programs', 1)
                ->has('levels', 4)
                ->has('statuses', 3)
                ->has('filters.search'));
    }

    public function test_edit_screen_receives_course_prerequisites_and_options(): void
    {
        $course = Course::factory()->create(['department_id' => $this->department->id, 'code' => 'CS201']);
        $base = Course::factory()->create(['department_id' => $this->department->id, 'code' => 'CS101']);
        $free = Course::factory()->create(['department_id' => $this->department->id, 'code' => 'CS102']);
        Course::factory()->archived()->create(['department_id' => $this->department->id]);
        $course->prerequisites()->attach($base->id, ['is_strict' => true]);

        $this->actingAs($this->universityAdmin)
            ->get("/courses/{$course->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Courses/Edit')
                ->where('course.code', 'CS201')
                ->has('course.prerequisites', 1)
                ->where('course.prerequisites.0.code', 'CS101')
                // Not itself, not already chosen, not archived.
                ->has('prerequisiteOptions', 1)
                ->where('prerequisiteOptions.0.id', $free->id));
    }

    // ---------------------------------------------------------- web CRUD ---

    public function test_university_admin_can_create_update_and_delete_via_web(): void
    {
        $this->actingAs($this->universityAdmin)
            ->post('/courses', $this->payload(['code' => 'CS101']))
            ->assertSessionHas('success');

        $course = Course::query()->where('code', 'CS101')->firstOrFail();

        $this->actingAs($this->universityAdmin)
            ->put("/courses/{$course->id}", $this->payload(['code' => 'CS101', 'name' => 'Renamed']))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'name' => 'Renamed']);

        $this->actingAs($this->universityAdmin)->delete("/courses/{$course->id}")->assertRedirect('/courses');
        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    public function test_web_validation_errors_are_returned_to_the_form(): void
    {
        $this->actingAs($this->superAdmin)
            ->from('/courses')
            ->post('/courses', [])
            ->assertSessionHasErrors(['department_id', 'code', 'name', 'credits']);
    }

    // ---------------------------------------------------------- API CRUD ---

    public function test_api_crud_cycle(): void
    {
        $created = $this->actingAs($this->superAdmin)
            ->postJson('/api/courses', $this->payload(['code' => 'CS201', 'credits' => 3.5, 'status' => 'draft']))
            ->assertCreated()
            ->assertJsonPath('data.code', 'CS201')
            ->assertJsonPath('data.credits', 3.5)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.department.id', $this->department->id);

        $id = $created->json('data.id');

        $this->actingAs($this->superAdmin)->getJson("/api/courses/{$id}")
            ->assertOk()->assertJsonPath('data.code', 'CS201')->assertJsonCount(0, 'data.prerequisites');

        $this->actingAs($this->superAdmin)
            ->patchJson("/api/courses/{$id}", $this->payload(['code' => 'CS201', 'name' => 'Updated', 'status' => 'active']))
            ->assertOk()->assertJsonPath('data.name', 'Updated')->assertJsonPath('data.status', 'active');

        $this->actingAs($this->superAdmin)->deleteJson("/api/courses/{$id}")->assertNoContent();
        $this->actingAs($this->superAdmin)->getJson("/api/courses/{$id}")->assertNotFound();
    }

    public function test_new_course_defaults_to_active(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('/api/courses', $this->payload())
            ->assertCreated()->assertJsonPath('data.status', 'active');
    }

    // -------------------------------------------------------- validation ---

    public function test_required_fields_ranges_and_enums_are_validated(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('/api/courses', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['department_id', 'code', 'name', 'credits']);

        $this->actingAs($this->superAdmin)
            ->postJson('/api/courses', $this->payload(['credits' => 0, 'course_level' => 'wizard', 'status' => 'archived', 'lecture_hours' => -1]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['credits', 'course_level', 'status', 'lecture_hours']);
    }

    public function test_code_must_be_unique_and_may_be_kept_on_update(): void
    {
        $course = Course::factory()->create(['department_id' => $this->department->id, 'code' => 'CS101']);

        $this->actingAs($this->superAdmin)
            ->postJson('/api/courses', $this->payload(['code' => 'CS101']))
            ->assertUnprocessable()->assertJsonValidationErrors(['code']);

        $this->actingAs($this->superAdmin)
            ->putJson("/api/courses/{$course->id}", $this->payload(['code' => 'CS101', 'credits' => 4]))
            ->assertOk()->assertJsonPath('data.credits', 4);
    }

    public function test_course_cannot_be_created_under_an_archived_department(): void
    {
        $archived = Department::factory()->create(['faculty_id' => $this->department->faculty_id]);
        $archived->delete();

        $this->actingAs($this->superAdmin)
            ->postJson('/api/courses', $this->payload(['department_id' => $archived->id]))
            ->assertUnprocessable()->assertJsonValidationErrors(['department_id']);
    }

    // --------------------------------------------------- list / filtering ---

    public function test_list_supports_search_filters_sorting_and_page_cap(): void
    {
        $other = Department::factory()->create();
        $program = Program::factory()->create(['department_id' => $this->department->id]);
        $a = Course::factory()->create(['department_id' => $this->department->id, 'code' => 'AAA1', 'name' => 'Alpha Systems', 'course_level' => 'advanced']);
        Course::factory()->draft()->create(['department_id' => $this->department->id, 'code' => 'BBB1', 'name' => 'Beta']);
        Course::factory()->create(['department_id' => $other->id, 'code' => 'CCC1', 'name' => 'Gamma']);
        $program->courses()->attach($a->id, ['is_required' => true, 'suggested_semester' => 1]);

        $this->actingAs($this->superAdmin)->getJson('/api/courses?search=alpha')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.code', 'AAA1')
            ->assertJsonPath('data.0.programs_count', 1)->assertJsonPath('data.0.prerequisites_count', 0);

        $this->actingAs($this->superAdmin)->getJson("/api/courses?filters[department_id]={$this->department->id}")
            ->assertOk()->assertJsonCount(2, 'data');

        $this->actingAs($this->superAdmin)->getJson("/api/courses?filters[faculty_id]={$other->faculty_id}")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.code', 'CCC1');

        $this->actingAs($this->superAdmin)->getJson("/api/courses?filters[program_id]={$program->id}")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.code', 'AAA1');

        $this->actingAs($this->superAdmin)->getJson('/api/courses?filters[status]=draft')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.code', 'BBB1');

        $this->actingAs($this->superAdmin)->getJson('/api/courses?filters[course_level]=advanced')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($this->superAdmin)->getJson('/api/courses?sort_by=code&sort_dir=desc')
            ->assertOk()->assertJsonPath('data.0.code', 'CCC1');

        $this->actingAs($this->superAdmin)->getJson('/api/courses?sort_by=password;drop')
            ->assertOk()->assertJsonPath('data.0.code', 'AAA1');

        $this->actingAs($this->superAdmin)->getJson('/api/courses?per_page=5000')
            ->assertOk()->assertJsonPath('meta.per_page', 100);
    }

    // ------------------------------------------------- archive / delete ---

    public function test_archive_and_reactivate(): void
    {
        $course = Course::factory()->create(['department_id' => $this->department->id]);

        $this->actingAs($this->universityAdmin)->postJson("/api/courses/{$course->id}/archive")
            ->assertOk()->assertJsonPath('data.status', 'archived');

        $this->actingAs($this->universityAdmin)->postJson("/api/courses/{$course->id}/reactivate")
            ->assertOk()->assertJsonPath('data.status', 'active');

        $this->actingAs($this->universityAdmin)->post("/courses/{$course->id}/archive")->assertSessionHas('success');
        $this->assertSame('archived', $course->refresh()->status);
    }

    public function test_plain_update_cannot_un_archive_a_course(): void
    {
        $course = Course::factory()->archived()->create(['department_id' => $this->department->id]);

        $this->actingAs($this->superAdmin)
            ->putJson("/api/courses/{$course->id}", $this->payload(['code' => $course->code, 'name' => 'Edited', 'status' => 'active']))
            ->assertOk()
            ->assertJsonPath('data.name', 'Edited')
            ->assertJsonPath('data.status', 'archived');
    }

    public function test_delete_is_blocked_while_a_program_curriculum_includes_the_course(): void
    {
        $course = Course::factory()->create(['department_id' => $this->department->id]);
        $program = Program::factory()->create(['department_id' => $this->department->id]);
        $program->courses()->attach($course->id);

        $this->actingAs($this->superAdmin)->deleteJson("/api/courses/{$course->id}")
            ->assertStatus(409)
            ->assertJsonPath('message', 'This course is still used by a program curriculum and cannot be deleted. Archive it instead.');

        $this->actingAs($this->superAdmin)->delete("/courses/{$course->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    public function test_delete_is_blocked_while_the_course_is_a_prerequisite(): void
    {
        [$base, $advanced] = $this->chain(2);

        $this->actingAs($this->superAdmin)->deleteJson("/api/courses/{$base->id}")->assertStatus(409);

        // The dependent course may be deleted: its own prerequisite rows cascade.
        $this->actingAs($this->superAdmin)->deleteJson("/api/courses/{$advanced->id}")->assertNoContent();
        $this->assertDatabaseCount('course_prerequisites', 0);
    }

    public function test_delete_is_blocked_while_course_offerings_exist(): void
    {
        $course = Course::factory()->create(['department_id' => $this->department->id]);
        DB::table('course_offerings')->insert([
            'course_id' => $course->id,
            'semester_id' => Semester::factory()->create()->id,
        ]);

        $this->actingAs($this->superAdmin)->deleteJson("/api/courses/{$course->id}")->assertStatus(409);
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    public function test_department_delete_guard_now_sees_courses(): void
    {
        Course::factory()->create(['department_id' => $this->department->id]);

        $this->actingAs($this->superAdmin)->deleteJson("/api/departments/{$this->department->id}")->assertStatus(409);
    }

    // ------------------------------------------------------ prerequisites ---

    public function test_prerequisite_can_be_added_and_removed(): void
    {
        $course = Course::factory()->create(['department_id' => $this->department->id]);
        $base = Course::factory()->create(['department_id' => $this->department->id, 'code' => 'BASE1']);

        $this->actingAs($this->superAdmin)
            ->postJson("/api/courses/{$course->id}/prerequisites", ['prerequisite_course_id' => $base->id, 'is_strict' => false])
            ->assertCreated()
            ->assertJsonCount(1, 'data.prerequisites')
            ->assertJsonPath('data.prerequisites.0.code', 'BASE1')
            ->assertJsonPath('data.prerequisites.0.is_strict', false);

        $this->actingAs($this->superAdmin)->deleteJson("/api/courses/{$course->id}/prerequisites/{$base->id}")->assertNoContent();
        $this->assertDatabaseCount('course_prerequisites', 0);

        // Web routes mirror the API.
        $this->actingAs($this->superAdmin)
            ->post("/courses/{$course->id}/prerequisites", ['prerequisite_course_id' => $base->id])
            ->assertSessionHas('success');
        $this->assertDatabaseCount('course_prerequisites', 1);
        $this->actingAs($this->superAdmin)->delete("/courses/{$course->id}/prerequisites/{$base->id}")->assertSessionHas('success');
    }

    public function test_prerequisite_cannot_be_the_course_itself(): void
    {
        $course = Course::factory()->create(['department_id' => $this->department->id]);

        $this->actingAs($this->superAdmin)
            ->postJson("/api/courses/{$course->id}/prerequisites", ['prerequisite_course_id' => $course->id])
            ->assertUnprocessable()->assertJsonValidationErrors(['prerequisite_course_id']);
    }

    public function test_prerequisite_must_exist_and_not_be_a_duplicate_or_archived(): void
    {
        [$base, $course] = $this->chain(2);
        $archived = Course::factory()->archived()->create(['department_id' => $this->department->id]);

        $this->actingAs($this->superAdmin)
            ->postJson("/api/courses/{$course->id}/prerequisites", ['prerequisite_course_id' => 999999])
            ->assertUnprocessable()->assertJsonValidationErrors(['prerequisite_course_id']);

        $this->actingAs($this->superAdmin)
            ->postJson("/api/courses/{$course->id}/prerequisites", ['prerequisite_course_id' => $base->id])
            ->assertUnprocessable()->assertJsonValidationErrors(['prerequisite_course_id']);

        $this->actingAs($this->superAdmin)
            ->postJson("/api/courses/{$course->id}/prerequisites", ['prerequisite_course_id' => $archived->id])
            ->assertUnprocessable()->assertJsonValidationErrors(['prerequisite_course_id']);
    }

    public function test_direct_prerequisite_cycle_is_rejected(): void
    {
        [$base, $advanced] = $this->chain(2); // advanced requires base

        // base requiring advanced would close the loop.
        $this->actingAs($this->superAdmin)
            ->postJson("/api/courses/{$base->id}/prerequisites", ['prerequisite_course_id' => $advanced->id])
            ->assertUnprocessable()->assertJsonValidationErrors(['prerequisite_course_id']);

        $this->assertDatabaseCount('course_prerequisites', 1);
    }

    public function test_transitive_prerequisite_cycle_is_rejected(): void
    {
        [$a, $b, $c] = $this->chain(3); // c -> b -> a

        $this->actingAs($this->superAdmin)
            ->postJson("/api/courses/{$a->id}/prerequisites", ['prerequisite_course_id' => $c->id])
            ->assertUnprocessable()->assertJsonValidationErrors(['prerequisite_course_id']);

        // A diamond (two paths to the same course) is not a cycle.
        $d = Course::factory()->create(['department_id' => $this->department->id]);
        $this->actingAs($this->superAdmin)
            ->postJson("/api/courses/{$d->id}/prerequisites", ['prerequisite_course_id' => $a->id])->assertCreated();
        $this->actingAs($this->superAdmin)
            ->postJson("/api/courses/{$d->id}/prerequisites", ['prerequisite_course_id' => $c->id])->assertCreated();
    }

    public function test_removing_an_unknown_prerequisite_is_a_404(): void
    {
        $course = Course::factory()->create(['department_id' => $this->department->id]);
        $other = Course::factory()->create(['department_id' => $this->department->id]);

        $this->actingAs($this->superAdmin)->deleteJson("/api/courses/{$course->id}/prerequisites/{$other->id}")->assertNotFound();
    }

    // ---------------------------------------------------------- curriculum ---

    public function test_course_can_be_added_updated_and_removed_from_a_curriculum(): void
    {
        $program = Program::factory()->create(['department_id' => $this->department->id]);
        $course = Course::factory()->create(['department_id' => $this->department->id, 'code' => 'CS101']);

        $this->actingAs($this->universityAdmin)
            ->postJson("/api/programs/{$program->id}/courses", ['course_id' => $course->id, 'is_required' => true, 'suggested_semester' => 2])
            ->assertCreated()
            ->assertJsonCount(1, 'data.courses')
            ->assertJsonPath('data.courses.0.code', 'CS101')
            ->assertJsonPath('data.courses.0.is_required', true)
            ->assertJsonPath('data.courses.0.suggested_semester', 2);

        $this->actingAs($this->universityAdmin)
            ->patchJson("/api/programs/{$program->id}/courses/{$course->id}", ['is_required' => false, 'suggested_semester' => 4])
            ->assertOk()
            ->assertJsonPath('data.courses.0.is_required', false)
            ->assertJsonPath('data.courses.0.suggested_semester', 4);

        $this->actingAs($this->universityAdmin)->getJson("/api/programs/{$program->id}")
            ->assertOk()->assertJsonCount(1, 'data.courses');

        $this->actingAs($this->universityAdmin)->deleteJson("/api/programs/{$program->id}/courses/{$course->id}")->assertNoContent();

        // Only the link goes; the course itself is untouched.
        $this->assertDatabaseCount('course_programs', 0);
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    public function test_curriculum_rejects_duplicates_archived_and_bad_input(): void
    {
        $program = Program::factory()->create(['department_id' => $this->department->id]);
        $course = Course::factory()->create(['department_id' => $this->department->id]);
        $archived = Course::factory()->archived()->create(['department_id' => $this->department->id]);
        $program->courses()->attach($course->id);

        $this->actingAs($this->superAdmin)
            ->postJson("/api/programs/{$program->id}/courses", ['course_id' => $course->id])
            ->assertUnprocessable()->assertJsonValidationErrors(['course_id']);

        $this->actingAs($this->superAdmin)
            ->postJson("/api/programs/{$program->id}/courses", ['course_id' => $archived->id])
            ->assertUnprocessable()->assertJsonValidationErrors(['course_id']);

        $this->actingAs($this->superAdmin)
            ->postJson("/api/programs/{$program->id}/courses", ['course_id' => 999999])
            ->assertUnprocessable()->assertJsonValidationErrors(['course_id']);

        $fresh = Course::factory()->create(['department_id' => $this->department->id]);
        $this->actingAs($this->superAdmin)
            ->postJson("/api/programs/{$program->id}/courses", ['course_id' => $fresh->id, 'suggested_semester' => 99])
            ->assertUnprocessable()->assertJsonValidationErrors(['suggested_semester']);
    }

    public function test_same_course_may_belong_to_several_programs(): void
    {
        $course = Course::factory()->create(['department_id' => $this->department->id]);
        $one = Program::factory()->create(['department_id' => $this->department->id]);
        $two = Program::factory()->create(['department_id' => $this->department->id]);

        $this->actingAs($this->superAdmin)->postJson("/api/programs/{$one->id}/courses", ['course_id' => $course->id])->assertCreated();
        $this->actingAs($this->superAdmin)->postJson("/api/programs/{$two->id}/courses", ['course_id' => $course->id])->assertCreated();

        $this->actingAs($this->superAdmin)->getJson("/api/courses/{$course->id}")
            ->assertOk()->assertJsonCount(2, 'data.programs');
    }

    public function test_curriculum_changes_for_a_course_outside_the_program_are_404(): void
    {
        $program = Program::factory()->create(['department_id' => $this->department->id]);
        $course = Course::factory()->create(['department_id' => $this->department->id]);

        $this->actingAs($this->superAdmin)->patchJson("/api/programs/{$program->id}/courses/{$course->id}", ['is_required' => true])->assertNotFound();
        $this->actingAs($this->superAdmin)->deleteJson("/api/programs/{$program->id}/courses/{$course->id}")->assertNotFound();
    }

    public function test_program_edit_screen_receives_curriculum_and_available_courses(): void
    {
        $program = Program::factory()->create(['department_id' => $this->department->id]);
        $inside = Course::factory()->create(['department_id' => $this->department->id, 'code' => 'IN1']);
        $outside = Course::factory()->create(['department_id' => $this->department->id, 'code' => 'OUT1']);
        Course::factory()->archived()->create(['department_id' => $this->department->id]);
        $program->courses()->attach($inside->id, ['is_required' => true, 'suggested_semester' => 1]);

        $this->actingAs($this->superAdmin)
            ->get("/programs/{$program->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Programs/Edit')
                ->has('program.courses', 1)
                ->where('program.courses.0.code', 'IN1')
                ->has('availableCourses', 1)
                ->where('availableCourses.0.id', $outside->id));
    }

    public function test_curriculum_web_actions(): void
    {
        $program = Program::factory()->create(['department_id' => $this->department->id]);
        $course = Course::factory()->create(['department_id' => $this->department->id]);

        $this->actingAs($this->superAdmin)
            ->post("/programs/{$program->id}/courses", ['course_id' => $course->id, 'is_required' => true])
            ->assertSessionHas('success');
        $this->actingAs($this->superAdmin)
            ->put("/programs/{$program->id}/courses/{$course->id}", ['suggested_semester' => 3])
            ->assertSessionHas('success');
        $this->assertSame(3, $program->courses()->first()->pivot->suggested_semester);
        $this->actingAs($this->superAdmin)
            ->delete("/programs/{$program->id}/courses/{$course->id}")
            ->assertSessionHas('success');
        $this->assertDatabaseCount('course_programs', 0);
    }

    // ------------------------------------------------------------- seeding ---

    public function test_seeder_is_idempotent_acyclic_and_builds_curricula(): void
    {
        $this->seed(UniversityStructureSeeder::class);
        $this->seed(ProgramSeeder::class);
        $this->seed(CourseSeeder::class);
        $courses = Course::query()->count();
        $links = DB::table('course_prerequisites')->count();
        $curriculum = DB::table('course_programs')->count();

        $this->seed(CourseSeeder::class);

        $this->assertSame($courses, Course::query()->count());
        $this->assertSame($links, DB::table('course_prerequisites')->count());
        $this->assertSame($curriculum, DB::table('course_programs')->count());
        $this->assertGreaterThanOrEqual(10, $courses);

        $bscs = Program::query()->where('code', 'BSCS')->firstOrFail();
        $this->assertGreaterThanOrEqual(5, $bscs->courses()->count());

        // Every seeded prerequisite must be acyclic: nothing reaches itself.
        foreach (Course::query()->get() as $course) {
            $this->assertFalse($this->reaches($course->id, $course->id, true), "{$course->code} depends on itself");
        }
    }

    // ------------------------------------------------------------- helpers ---

    /**
     * Build a prerequisite chain: [0] is the base; each later course requires
     * the one before it.
     *
     * @return list<Course>
     */
    private function chain(int $length): array
    {
        $courses = [];

        for ($i = 0; $i < $length; $i++) {
            $course = Course::factory()->create(['department_id' => $this->department->id]);

            if ($i > 0) {
                $course->prerequisites()->attach($courses[$i - 1]->id, ['is_strict' => true]);
            }

            $courses[] = $course;
        }

        return $courses;
    }

    private function reaches(int $from, int $target, bool $first = false): bool
    {
        $seen = [];
        $queue = DB::table('course_prerequisites')->where('course_id', $from)->pluck('prerequisite_course_id')->all();

        while ($queue !== []) {
            $current = (int) array_pop($queue);

            if ($current === $target) {
                return true;
            }

            if (isset($seen[$current])) {
                continue;
            }
            $seen[$current] = true;

            foreach (DB::table('course_prerequisites')->where('course_id', $current)->pluck('prerequisite_course_id') as $id) {
                $queue[] = $id;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'department_id' => $this->department->id,
            'code' => 'CRS'.fake()->unique()->numerify('####'),
            'name' => 'Course '.fake()->unique()->numerify('####'),
            'credits' => 3,
            'lecture_hours' => 30,
            'lab_hours' => 15,
            'course_level' => 'introductory',
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
}
