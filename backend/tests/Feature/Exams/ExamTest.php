<?php

namespace Tests\Feature\Exams;

use App\Enums\Role;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Lecturer;
use App\Models\Role as RoleModel;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Module 9.13 — exams, scheduling clashes, results entry and release, scoping.
 */
class ExamTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Semester $semester;

    private Section $section;

    private Lecturer $lecturer;

    private Student $student;

    private Enrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-03-04 12:00:00');

        $this->admin = User::factory()->superAdmin()->create();
        $this->semester = Semester::factory()->create(['status' => 'open', 'start_date' => '2026-02-01', 'end_date' => '2026-06-30']);
        $this->section = $this->newSection();
        $this->lecturer = Lecturer::factory()->create();
        $this->section->lecturers()->attach($this->lecturer->id, ['role' => 'primary']);
        $this->student = Student::factory()->create();
        $this->enrollment = app(EnrollmentService::class)->enroll($this->student, $this->section);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ---------------------------------------------------------------- exams ---

    public function test_lecturer_creates_updates_releases_and_deletes(): void
    {
        $id = $this->actingAs($this->lecturer->user)->postJson("/api/sections/{$this->section->id}/exams", $this->payload())
            ->assertCreated()->assertJsonPath('data.is_published', false)->assertJsonPath('data.start_time', '09:00')->json('data.id');

        $this->actingAs($this->lecturer->user)->putJson("/api/exams/{$id}", $this->payload(['title' => 'Mid', 'location' => 'B-201']))->assertOk()->assertJsonPath('data.location', 'B-201');
        $this->actingAs($this->lecturer->user)->postJson("/api/exams/{$id}/publish")->assertOk()->assertJsonPath('data.is_published', true);
        $this->actingAs($this->lecturer->user)->deleteJson("/api/exams/{$id}")->assertNoContent();

        // Not deletable once results exist.
        $exam = Exam::factory()->create(['section_id' => $this->section->id]);
        ExamResult::query()->create(['exam_id' => $exam->id, 'enrollment_id' => $this->enrollment->id, 'score' => 50]);
        $this->actingAs($this->lecturer->user)->deleteJson("/api/exams/{$exam->id}")->assertStatus(409);
    }

    public function test_exam_validation(): void
    {
        $url = "/api/sections/{$this->section->id}/exams";
        $as = $this->actingAs($this->lecturer->user);

        $as->postJson($url, $this->payload(['exam_type' => 'oral']))->assertJsonValidationErrors('exam_type');
        $as->postJson($url, $this->payload(['max_score' => 0]))->assertJsonValidationErrors('max_score');
        $as->postJson($url, $this->payload(['end_time' => '08:00']))->assertJsonValidationErrors('end_time');
        $as->postJson($url, $this->payload(['scheduled_date' => null]))->assertJsonValidationErrors('scheduled_date');
        $as->postJson($url, $this->payload(['scheduled_date' => '2026-07-15']))->assertJsonValidationErrors('scheduled_date');

        // Untimed exam without a date is fine.
        $as->postJson($url, $this->payload(['scheduled_date' => null, 'start_time' => null, 'end_time' => null, 'weight' => 60]))->assertCreated();
        // Weights of the section may not exceed 100.
        $as->postJson($url, $this->payload(['weight' => 45]))->assertJsonValidationErrors('weight');
        $as->postJson($url, $this->payload(['weight' => 40]))->assertCreated();

        $this->semester->update(['status' => 'completed']);
        $this->actingAs($this->admin)->postJson($url, $this->payload(['weight' => 0]))->assertStatus(409);
    }

    public function test_time_clashes(): void
    {
        $as = $this->actingAs($this->admin);
        $as->postJson("/api/sections/{$this->section->id}/exams", $this->payload(['weight' => 10]))->assertCreated();

        // Same section, overlapping.
        $as->postJson("/api/sections/{$this->section->id}/exams", $this->payload(['weight' => 10, 'start_time' => '10:00', 'end_time' => '12:00']))->assertStatus(409);
        // Back-to-back is fine.
        $as->postJson("/api/sections/{$this->section->id}/exams", $this->payload(['weight' => 10, 'start_time' => '11:00', 'end_time' => '12:00']))->assertCreated();

        // Another section without shared students: fine.
        $other = $this->newSection();
        $as->postJson("/api/sections/{$other->id}/exams", $this->payload())->assertCreated();

        // A section sharing a student: clash.
        $shared = $this->newSection();
        app(EnrollmentService::class)->enroll($this->student, $shared);
        $as->postJson("/api/sections/{$shared->id}/exams", $this->payload(['start_time' => '10:30', 'end_time' => '11:30']))
            ->assertStatus(409)->assertJsonPath('message', fn ($message) => str_contains($message, 'students also enrolled in'));
        $as->postJson("/api/sections/{$shared->id}/exams", $this->payload(['scheduled_date' => '2026-04-16']))->assertCreated();
    }

    // -------------------------------------------------------------- results ---

    public function test_results_entry(): void
    {
        $exam = Exam::factory()->create(['section_id' => $this->section->id, 'scheduled_date' => '2026-03-02', 'max_score' => 50]);
        $classmate = app(EnrollmentService::class)->enroll(Student::factory()->create(), $this->section);
        $url = "/api/exams/{$exam->id}/results";
        $as = $this->actingAs($this->lecturer->user);

        $as->postJson($url, ['results' => [
            ['enrollment_id' => $this->enrollment->id, 'score' => 42.5, 'remarks' => 'Good'],
            ['enrollment_id' => $classmate->id, 'score' => null, 'remarks' => null], // ignored
        ]])->assertOk()->assertJsonCount(2, 'results')->assertJsonPath('data.results_count', 1);

        // Upsert, never duplicate.
        $as->postJson($url, ['results' => [['enrollment_id' => $this->enrollment->id, 'score' => 45]]])->assertOk();
        $this->assertSame(1, ExamResult::query()->count());
        $this->assertEquals(45, ExamResult::query()->first()->score);

        $as->postJson($url, ['results' => [['enrollment_id' => $this->enrollment->id, 'score' => 51]]])->assertJsonValidationErrors('results.0.score');
        $foreign = app(EnrollmentService::class)->enroll(Student::factory()->create(), $this->newSection());
        $as->postJson($url, ['results' => [['enrollment_id' => $foreign->id, 'score' => 10]]])->assertJsonValidationErrors('results.0.enrollment_id');

        // Max score cannot drop below a recorded score.
        $as->putJson("/api/exams/{$exam->id}", $this->payload(['scheduled_date' => '2026-03-02', 'max_score' => 40]))->assertJsonValidationErrors('max_score');

        // Correction.
        $result = ExamResult::query()->first();
        $as->patchJson("/api/exam-results/{$result->id}", ['score' => 48, 'remarks' => 'Remarked'])->assertOk();
        $this->assertEquals(48, $result->refresh()->score);
        $as->patchJson("/api/exam-results/{$result->id}", ['score' => 60])->assertJsonValidationErrors('score');

        // Not before the exam date.
        $future = Exam::factory()->create(['section_id' => $this->section->id, 'scheduled_date' => '2026-04-01', 'weight' => 0]);
        $as->postJson("/api/exams/{$future->id}/results", ['results' => [['enrollment_id' => $this->enrollment->id, 'score' => 10]]])->assertJsonValidationErrors('results');
    }

    public function test_students_see_schedule_and_only_released_results(): void
    {
        $exam = Exam::factory()->create(['section_id' => $this->section->id, 'scheduled_date' => '2026-03-02']);
        ExamResult::query()->create(['exam_id' => $exam->id, 'enrollment_id' => $this->enrollment->id, 'score' => 77]);
        $me = $this->actingAs($this->student->user);

        $me->getJson("/api/sections/{$this->section->id}/exams")->assertOk()->assertJsonCount(1, 'data')->assertJsonMissingPath('data.0.results_count');
        $me->getJson("/api/exams/{$exam->id}")->assertOk()->assertJsonPath('my_result', null)->assertJsonMissingPath('results');
        $me->getJson("/api/students/{$this->student->id}/exams")->assertOk()->assertJsonPath('data.0.my_result', null);

        $exam->update(['is_published' => true]);
        $me->getJson("/api/exams/{$exam->id}")->assertJsonPath('my_result.score', 77);
        $me->getJson("/api/students/{$this->student->id}/exams")->assertJsonPath('data.0.my_result.score', 77)->assertJsonPath('data.0.course.code', $this->section->offering->course->code);

        // Students never write and never see others.
        $me->postJson("/api/exams/{$exam->id}/results", ['results' => [['enrollment_id' => $this->enrollment->id, 'score' => 100]]])->assertForbidden();
        $me->getJson('/api/students/'.Student::factory()->create()->id.'/exams')->assertForbidden();
        $this->actingAs(Student::factory()->create()->user)->getJson("/api/exams/{$exam->id}")->assertForbidden();
    }

    public function test_roles(): void
    {
        $exam = Exam::factory()->create(['section_id' => $this->section->id]);
        $faculty = User::factory()->create(['role_id' => RoleModel::factory()->withSlug(Role::FacultyAdmin->value)->create()->id]);
        $outsider = Lecturer::factory()->create();

        $this->actingAs($faculty)->getJson("/api/exams/{$exam->id}")->assertOk()->assertJsonCount(1, 'results');
        $this->actingAs($faculty)->postJson("/api/sections/{$this->section->id}/exams", $this->payload())->assertForbidden();
        $this->actingAs($faculty)->getJson("/api/students/{$this->student->id}/exams")->assertOk();

        $this->actingAs($outsider->user)->getJson("/api/sections/{$this->section->id}/exams")->assertForbidden();
        $this->actingAs($outsider->user)->putJson("/api/exams/{$exam->id}", $this->payload())->assertForbidden();

        $this->lecturer->update(['is_active' => false]);
        $this->actingAs($this->lecturer->user)->postJson("/api/exams/{$exam->id}/publish")->assertForbidden();
    }

    public function test_web_pages(): void
    {
        $exam = Exam::factory()->released()->create(['section_id' => $this->section->id, 'scheduled_date' => '2026-03-02']);
        ExamResult::query()->create(['exam_id' => $exam->id, 'enrollment_id' => $this->enrollment->id, 'score' => 66]);

        $this->actingAs($this->lecturer->user)->get("/exams/sections/{$this->section->id}")->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Exams/Section')->where('canManage', true)->where('selectedId', $exam->id)->has('roster', 1)->where('totalWeight', 30));

        $this->actingAs($this->student->user)->get("/exams/sections/{$this->section->id}")->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Exams/Section')->where('canReview', false)->has('roster', 0)->where('exams.0.my_result.score', 66));

        $this->actingAs($this->student->user)->get('/my-exams')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Exams/Mine')->has('exams', 1));

        $this->actingAs($this->lecturer->user)->post("/exams/{$exam->id}/results", ['results' => [['enrollment_id' => $this->enrollment->id, 'score' => 70]]])->assertRedirect();
        $this->assertEquals(70, ExamResult::query()->first()->score);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return ['exam_type' => 'midterm', 'title' => 'Midterm', 'weight' => 30, 'max_score' => 100, 'scheduled_date' => '2026-04-15', 'start_time' => '09:00', 'end_time' => '11:00', 'location' => 'Hall A', ...$overrides];
    }

    private function newSection(): Section
    {
        $offering = CourseOffering::factory()->create(['course_id' => Course::factory()->create()->id, 'semester_id' => $this->semester->id, 'status' => 'open']);

        return Section::factory()->create(['course_offering_id' => $offering->id, 'status' => 'open', 'capacity' => 30]);
    }
}
