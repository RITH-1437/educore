<?php

namespace Tests\Feature\Catalog;

use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\Program;
use App\Models\Room;
use App\Models\ScheduleEntry;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Report 53 — the public course catalog: what visitors see (and do not),
 * search, filters, this semester's sections, lecturers switch, API.
 */
class CatalogTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private Course $offered;

    private Course $notOffered;

    private Lecturer $lecturer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create(['code' => 'GIC', 'name' => 'Information Engineering']);
        $semester = Semester::factory()->create(['status' => 'open', 'name' => 'Semester 1']);

        $this->offered = Course::factory()->create(['department_id' => $this->department->id, 'code' => 'CS101', 'name' => 'Introduction to Programming', 'description' => 'Variables, loops and functions in Python.', 'credits' => 3]);
        $this->notOffered = Course::factory()->create(['department_id' => $this->department->id, 'code' => 'CS390', 'name' => 'Compilers', 'description' => 'Parsing and code generation.']);
        Program::factory()->create(['department_id' => $this->department->id, 'name' => 'Computer Science'])->courses()->attach($this->offered->id, ['is_required' => true]);

        // Not public: a draft course, an archived one, a course of an inactive department.
        Course::factory()->create(['department_id' => $this->department->id, 'code' => 'CS999', 'status' => Course::STATUS_DRAFT]);
        Course::factory()->create(['department_id' => $this->department->id, 'code' => 'CS998', 'status' => Course::STATUS_ARCHIVED]);
        Course::factory()->create(['department_id' => Department::factory()->create(['is_active' => false])->id, 'code' => 'XX100']);

        $offering = CourseOffering::factory()->create(['course_id' => $this->offered->id, 'semester_id' => $semester->id, 'status' => 'open']);
        $section = Section::factory()->create(['course_offering_id' => $offering->id, 'code' => 'A', 'status' => 'open', 'capacity' => 40]);
        $this->lecturer = Lecturer::factory()->create(['department_id' => $this->department->id, 'title' => 'Dr.', 'first_name' => 'Sokha', 'last_name' => 'Chan']);
        $section->lecturers()->attach($this->lecturer->id, ['role' => 'primary']);
        ScheduleEntry::factory()->create(['section_id' => $section->id, 'room_id' => Room::factory()->create(['code' => 'F-201'])->id, 'day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '09:30']);
        // A draft section of a draft offering of the other course: not "offered".
        $draftOffering = CourseOffering::factory()->create(['course_id' => $this->notOffered->id, 'semester_id' => $semester->id, 'status' => 'draft']);
        Section::factory()->create(['course_offering_id' => $draftOffering->id, 'status' => 'draft']);
        // A student in the section: never shown (no enrollment data is public).
        app(EnrollmentService::class)->enroll(Student::factory()->create(), $section);
        $this->offered->prerequisites()->attach($this->notOffered->id, ['is_strict' => true]);
    }

    public function test_visitors_browse_public_courses_only(): void
    {
        $this->get('/catalog')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Catalog/Index')
                ->where('courses.meta.total', 2)
                ->where('courses.data.0.code', 'CS101')
                ->where('courses.data.0.offered', true)
                ->where('courses.data.0.lecturers', ['Dr. Sokha Chan'])
                ->where('courses.data.1.code', 'CS390')
                ->where('courses.data.1.offered', false)
                ->has('departments', 1)
                ->where('departments.0.courses_count', 2)
                ->where('semester.name', fn (string $name) => str_ends_with($name, 'Semester 1')));

        // Search (code, name, description), department and "offered" filters.
        $this->get('/catalog?search=python')->assertInertia(fn (Assert $page) => $page->where('courses.meta.total', 1)->where('courses.data.0.code', 'CS101'));
        $this->get('/catalog?search=CS39')->assertInertia(fn (Assert $page) => $page->where('courses.data.0.code', 'CS390'));
        $this->get('/catalog?filters[offered]=1')->assertInertia(fn (Assert $page) => $page->where('courses.meta.total', 1));
        $this->get("/catalog?filters[department_id]={$this->department->id}")->assertInertia(fn (Assert $page) => $page->where('courses.meta.total', 2));
        $this->getJson('/api/catalog/courses?filters[department_id]=999999')->assertJsonValidationErrors('filters.department_id');
    }

    public function test_a_course_page_shows_its_details_and_this_semesters_sections(): void
    {
        $this->get("/catalog/courses/{$this->offered->id}")->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Catalog/Show')
                ->where('course.code', 'CS101')
                ->where('course.prerequisites.0.code', 'CS390')
                ->where('course.prerequisites.0.is_strict', true)
                ->where('course.programs.0.name', 'Computer Science')
                ->where('course.programs.0.is_required', true)
                ->where('course.sections.0.code', 'A')
                ->where('course.sections.0.lecturers', ['Dr. Sokha Chan'])
                ->where('course.sections.0.meetings.0', ['day' => 'Monday', 'start_time' => '08:00', 'end_time' => '09:30', 'room' => 'F-201']));

        foreach (['CS999', 'CS998', 'XX100'] as $code) {
            $this->get('/catalog/courses/'.Course::query()->where('code', $code)->value('id'))->assertNotFound();
        }
    }

    public function test_the_api_serves_the_same_public_data_and_nothing_private(): void
    {
        $this->getJson('/api/catalog/departments')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.code', 'GIC');
        $this->getJson('/api/catalog/courses?search=programming')->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.lecturers.0', 'Dr. Sokha Chan')
            ->assertJsonStructure(['meta' => ['semester' => ['id', 'name', 'status']]]);

        $body = $this->getJson("/api/catalog/courses/{$this->offered->id}")->assertOk()->getContent();
        // No contact details, seats, enrollment numbers or student data.
        $this->assertStringNotContainsString($this->lecturer->user->email, $body);
        foreach (['capacity', 'enrollments', 'student', 'email', 'phone'] as $private) {
            $this->assertStringNotContainsString("\"{$private}", $body);
        }

        $this->getJson('/api/catalog/courses/'.Course::query()->where('code', 'CS999')->value('id'))->assertNotFound();
    }

    public function test_lecturer_names_can_be_hidden(): void
    {
        config(['academics.catalog.show_lecturers' => false]);

        $this->getJson('/api/catalog/courses?search=CS101')->assertJsonPath('data.0.lecturers', []);
        $this->getJson("/api/catalog/courses/{$this->offered->id}")->assertJsonPath('data.sections.0.lecturers', [])->assertJsonPath('data.sections.0.code', 'A');
    }

    public function test_signed_in_users_open_it_too(): void
    {
        $student = Student::factory()->create();

        $this->actingAs($student->user)->get('/catalog')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Catalog/Index'));
        $this->actingAs($student->user)->get("/catalog/courses/{$this->offered->id}")->assertOk();
    }
}
