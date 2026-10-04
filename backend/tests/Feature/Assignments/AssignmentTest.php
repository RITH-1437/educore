<?php

namespace Tests\Feature\Assignments;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Lecturer;
use App\Models\Section;
use App\Models\Semester;
use App\Models\StoredFile;
use App\Models\Student;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Module 9.12 — assignments, private file submissions, grading, scoping.
 */
class AssignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Section $section;

    private Lecturer $lecturer;

    private Student $student;

    private Enrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-03-04 12:00:00');
        Storage::fake('s3');

        $this->admin = User::factory()->superAdmin()->create();
        $semester = Semester::factory()->create(['status' => 'open', 'start_date' => '2026-02-01', 'end_date' => '2026-06-30']);
        $offering = CourseOffering::factory()->create(['course_id' => Course::factory()->create()->id, 'semester_id' => $semester->id, 'status' => 'open']);
        $this->section = Section::factory()->create(['course_offering_id' => $offering->id, 'status' => 'open', 'capacity' => 30]);
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

    // ---------------------------------------------------------- assignments ---

    public function test_lecturer_creates_publishes_updates_and_deletes(): void
    {
        $id = $this->actingAs($this->lecturer->user)->postJson("/api/sections/{$this->section->id}/assignments", $this->payload())
            ->assertCreated()->assertJsonPath('data.is_published', false)->assertJsonPath('data.max_score', 50)->json('data.id');

        $this->actingAs($this->lecturer->user)->postJson("/api/assignments/{$id}/publish")->assertOk()->assertJsonPath('data.is_published', true);
        $this->actingAs($this->lecturer->user)->putJson("/api/assignments/{$id}", $this->payload(['title' => 'Renamed']))->assertOk()->assertJsonPath('data.title', 'Renamed');
        $this->actingAs($this->lecturer->user)->deleteJson("/api/assignments/{$id}")->assertNoContent();
    }

    public function test_assignment_validation(): void
    {
        $url = "/api/sections/{$this->section->id}/assignments";

        $this->actingAs($this->admin)->postJson($url, [])->assertUnprocessable()->assertJsonValidationErrors(['title', 'max_score', 'due_at', 'assignment_type']);
        $this->actingAs($this->admin)->postJson($url, $this->payload(['max_score' => 0, 'assignment_type' => 'essay']))->assertUnprocessable()->assertJsonValidationErrors(['max_score', 'assignment_type']);
        $this->actingAs($this->admin)->postJson($url, $this->payload(['due_at' => '2026-03-01 10:00']))->assertUnprocessable()->assertJsonValidationErrors(['due_at']); // past
        $this->actingAs($this->admin)->postJson($url, $this->payload(['due_at' => '2026-07-15 10:00']))->assertUnprocessable()->assertJsonValidationErrors(['due_at']); // after semester

        $this->section->offering->semester->update(['status' => 'completed']);
        $this->actingAs($this->admin)->postJson($url, $this->payload())->assertStatus(409);
    }

    public function test_roles(): void
    {
        $other = Lecturer::factory()->create();
        $departmentAdmin = $this->departmentAdminFor($this->departmentOfSection($this->section));
        $assignment = Assignment::factory()->create(['section_id' => $this->section->id]);
        $draft = Assignment::factory()->draft()->create(['section_id' => $this->section->id]);
        $outsider = Student::factory()->create();

        $this->actingAs($other->user)->postJson("/api/sections/{$this->section->id}/assignments", $this->payload())->assertForbidden();
        $this->actingAs($departmentAdmin)->postJson("/api/sections/{$this->section->id}/assignments", $this->payload())->assertForbidden();
        $this->actingAs($departmentAdmin)->getJson("/api/sections/{$this->section->id}/assignments")->assertOk()->assertJsonCount(2, 'data');
        // A Department Admin of another department (or none) cannot read this section.
        $this->actingAs($this->departmentAdminFor(Department::factory()->create()))->getJson("/api/sections/{$this->section->id}/assignments")->assertForbidden();
        $this->actingAs($this->departmentAdminFor(null))->getJson("/api/assignments/{$assignment->id}")->assertForbidden();
        $this->actingAs($this->student->user)->postJson("/api/sections/{$this->section->id}/assignments", $this->payload())->assertForbidden();

        // Students see published work of their own section only.
        $this->actingAs($this->student->user)->getJson("/api/sections/{$this->section->id}/assignments")->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($this->student->user)->getJson("/api/assignments/{$draft->id}")->assertForbidden();
        $this->actingAs($outsider->user)->getJson("/api/assignments/{$assignment->id}")->assertForbidden();
        $this->actingAs($this->student->user)->getJson("/api/assignments/{$assignment->id}/submissions")->assertForbidden();
    }

    // ---------------------------------------------------------- submissions ---

    public function test_student_submits_privately_and_resubmits_until_graded(): void
    {
        $assignment = Assignment::factory()->create(['section_id' => $this->section->id, 'max_score' => 20]);

        $this->actingAs($this->student->user)->post("/api/assignments/{$assignment->id}/submissions", ['file' => UploadedFile::fake()->create('work.pdf', 200, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('data.status', 'submitted')->assertJsonPath('data.file.name', 'work.pdf')
            ->assertJsonMissingPath('data.file.storage_key');

        $first = StoredFile::query()->firstOrFail();
        Storage::disk('s3')->assertExists($first->storage_key);
        $this->assertStringStartsWith("assignments/{$this->section->offering->course_id}/{$assignment->id}/submissions/{$this->student->id}/", $first->storage_key);

        // Replace: one submission row, old object deleted.
        $this->actingAs($this->student->user)->post("/api/assignments/{$assignment->id}/submissions", ['file' => UploadedFile::fake()->create('v2.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')], ['Accept' => 'application/json'])
            ->assertCreated();
        $this->assertSame(1, AssignmentSubmission::query()->count());
        $this->assertSame(1, StoredFile::query()->count());
        Storage::disk('s3')->assertMissing($first->storage_key);

        // Graded work is locked.
        $submission = AssignmentSubmission::query()->firstOrFail();
        $this->actingAs($this->lecturer->user)->postJson("/api/submissions/{$submission->id}/grade", ['score' => 18, 'feedback' => 'Good'])
            ->assertOk()->assertJsonPath('data.status', 'graded')->assertJsonPath('data.score', 18);
        $this->actingAs($this->student->user)->post("/api/assignments/{$assignment->id}/submissions", ['file' => UploadedFile::fake()->create('v3.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertStatus(409);
    }

    public function test_late_submission_is_flagged(): void
    {
        $assignment = Assignment::factory()->create(['section_id' => $this->section->id, 'due_at' => '2026-03-01 23:59']);

        $this->actingAs($this->student->user)->post("/api/assignments/{$assignment->id}/submissions", ['file' => UploadedFile::fake()->create('late.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('data.status', 'late');
    }

    public function test_file_validation(): void
    {
        $assignment = Assignment::factory()->create(['section_id' => $this->section->id]);
        $url = "/api/assignments/{$assignment->id}/submissions";

        $this->actingAs($this->student->user)->post($url, [], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors(['file']);
        $this->actingAs($this->student->user)->post($url, ['file' => UploadedFile::fake()->create('evil.exe', 10, 'application/x-msdownload')], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors(['file']);
        $this->actingAs($this->student->user)->post($url, ['file' => UploadedFile::fake()->create('big.pdf', 20000, 'application/pdf')], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors(['file']);
        $this->assertSame(0, AssignmentSubmission::query()->count());
    }

    public function test_who_may_submit(): void
    {
        $assignment = Assignment::factory()->create(['section_id' => $this->section->id]);
        $draft = Assignment::factory()->draft()->create(['section_id' => $this->section->id]);
        $file = fn () => ['file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')];

        $this->actingAs($this->lecturer->user)->post("/api/assignments/{$assignment->id}/submissions", $file(), ['Accept' => 'application/json'])->assertForbidden();
        $this->actingAs(Student::factory()->create()->user)->post("/api/assignments/{$assignment->id}/submissions", $file(), ['Accept' => 'application/json'])->assertForbidden();
        $this->actingAs($this->student->user)->post("/api/assignments/{$draft->id}/submissions", $file(), ['Accept' => 'application/json'])->assertForbidden();

        // A student who dropped the section can no longer submit.
        app(EnrollmentService::class)->drop($this->enrollment);
        $this->actingAs($this->student->user)->post("/api/assignments/{$assignment->id}/submissions", $file(), ['Accept' => 'application/json'])->assertForbidden();
    }

    // -------------------------------------------------------------- grading ---

    public function test_grading_rules_and_max_score_guard(): void
    {
        $assignment = Assignment::factory()->create(['section_id' => $this->section->id, 'max_score' => 20]);
        $submission = $this->submit($assignment);

        $this->actingAs($this->lecturer->user)->postJson("/api/submissions/{$submission->id}/grade", ['score' => 25])->assertUnprocessable()->assertJsonValidationErrors(['score']);
        $this->actingAs($this->lecturer->user)->postJson("/api/submissions/{$submission->id}/grade", ['score' => -1])->assertUnprocessable();
        $this->actingAs($this->student->user)->postJson("/api/submissions/{$submission->id}/grade", ['score' => 20])->assertForbidden();
        $this->actingAs(Lecturer::factory()->create()->user)->postJson("/api/submissions/{$submission->id}/grade", ['score' => 20])->assertForbidden();
        $this->actingAs($this->lecturer->user)->postJson("/api/submissions/{$submission->id}/grade", ['score' => 15])->assertOk();

        $this->actingAs($this->lecturer->user)->putJson("/api/assignments/{$assignment->id}", $this->payload(['max_score' => 10, 'due_at' => $assignment->due_at->toDateTimeString()]))
            ->assertUnprocessable()->assertJsonValidationErrors(['max_score']);
        $this->actingAs($this->lecturer->user)->deleteJson("/api/assignments/{$assignment->id}")->assertStatus(409);
        $this->actingAs($this->lecturer->user)->postJson("/api/assignments/{$assignment->id}/publish", ['published' => false])->assertStatus(409);
    }

    // ------------------------------------------------------------- download ---

    public function test_download_is_authorized(): void
    {
        $submission = $this->submit(Assignment::factory()->create(['section_id' => $this->section->id]));

        $this->actingAs($this->student->user)->get("/submissions/{$submission->id}/file")->assertOk()->assertDownload('a.pdf');
        $this->actingAs($this->lecturer->user)->getJson("/api/submissions/{$submission->id}/file")->assertOk();
        $this->actingAs(Student::factory()->create()->user)->get("/submissions/{$submission->id}/file")->assertForbidden();
        $this->actingAs(Lecturer::factory()->create()->user)->getJson("/api/submissions/{$submission->id}/file")->assertForbidden();
    }

    // ------------------------------------------------------------------ web ---

    public function test_web_pages(): void
    {
        $assignment = Assignment::factory()->create(['section_id' => $this->section->id]);
        $this->submit($assignment);

        $this->actingAs($this->lecturer->user)->get("/coursework/sections/{$this->section->id}")->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Coursework/Section')->where('canManage', true)->has('assignments', 1)->has("submissions.{$assignment->id}", 1));

        $this->actingAs($this->student->user)->get("/coursework/sections/{$this->section->id}")->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Coursework/Section')->where('canManage', false)->where('canReview', false)->where('assignments.0.my_submission.status', 'submitted'));

        $this->actingAs($this->student->user)->get('/my-assignments')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Coursework/Mine')->has('assignments', 1));

        $this->actingAs($this->lecturer->user)->post("/coursework/sections/{$this->section->id}", $this->payload())->assertSessionHas('success');
    }

    // ------------------------------------------------------------- helpers ---

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return ['title' => 'Lab report 1', 'max_score' => 50, 'due_at' => '2026-03-20 23:59', 'assignment_type' => 'homework', ...$overrides];
    }

    private function submit(Assignment $assignment): AssignmentSubmission
    {
        $this->actingAs($this->student->user)->post("/api/assignments/{$assignment->id}/submissions", ['file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])->assertCreated();

        return AssignmentSubmission::query()->latest('id')->firstOrFail();
    }
}
