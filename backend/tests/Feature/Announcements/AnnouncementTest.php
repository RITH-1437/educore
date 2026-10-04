<?php

namespace Tests\Feature\Announcements;

use App\Enums\Role;
use App\Models\Announcement;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\Program;
use App\Models\Role as RoleModel;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Module 9.19 — audience resolution, feed scoping, draft → published →
 * archived, lecturer targeting limits, authorization.
 */
class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Department $department;

    private Program $program;

    private Section $section;

    private Lecturer $lecturer;

    private Student $student;

    private Student $outsider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->superAdmin()->create();
        $this->department = Department::factory()->create();
        $this->program = Program::factory()->create(['department_id' => $this->department->id]);

        $semester = Semester::factory()->create(['status' => 'open']);
        $offering = CourseOffering::factory()->create(['course_id' => Course::factory()->create()->id, 'semester_id' => $semester->id, 'status' => 'open']);
        $this->section = Section::factory()->create(['course_offering_id' => $offering->id, 'status' => 'open', 'capacity' => 30]);
        $this->lecturer = Lecturer::factory()->create(['department_id' => $this->department->id]);
        $this->section->lecturers()->attach($this->lecturer->id, ['role' => 'primary']);

        $this->student = Student::factory()->inProgram($this->program)->create();
        app(EnrollmentService::class)->enroll($this->student, $this->section);
        $this->outsider = Student::factory()->inProgram()->create();
    }

    public function test_feed_resolves_audiences_from_memberships(): void
    {
        $ids = [];
        foreach ([
            'all' => null, 'students' => null, 'lecturers' => null, 'staff' => null,
            'department' => $this->department->id, 'program' => $this->program->id,
            'section' => $this->section->id, 'course' => $this->section->offering->course_id,
        ] as $type => $id) {
            $ids[$type] = $this->actingAs($this->admin)->postJson('/api/announcements', $this->payload(['audience_type' => $type, 'audience_id' => $id, 'publish' => true]))
                ->assertCreated()->assertJsonPath('data.publish_state', 'published')->json('data.id');
        }
        // The faculty level is gone: it is no longer a valid audience.
        $this->actingAs($this->admin)->postJson('/api/announcements', $this->payload(['audience_type' => 'faculty', 'audience_id' => $this->department->id]))->assertStatus(422)->assertJsonValidationErrors('audience_type');
        // A draft never reaches a feed.
        $this->actingAs($this->admin)->postJson('/api/announcements', $this->payload())->assertCreated()->assertJsonPath('data.publish_state', 'draft');

        $feed = fn (User $user) => collect($this->actingAs($user)->getJson('/api/announcements/feed?per_page=50')->assertOk()->json('data'))->pluck('id')->sort()->values()->all();
        $expect = fn (array $types) => collect($types)->map(fn ($t) => $ids[$t])->sort()->values()->all();

        $this->assertSame($expect(['all', 'students', 'department', 'program', 'section', 'course']), $feed($this->student->user));
        $this->assertSame($expect(['all', 'students']), $feed($this->outsider->user));
        $this->assertSame($expect(['all', 'lecturers', 'department', 'section', 'course']), $feed($this->lecturer->user));
        $this->assertSame($expect(['all', 'staff']), $feed($this->admin));
    }

    public function test_lifecycle_draft_publish_archive(): void
    {
        $id = $this->actingAs($this->admin)->postJson('/api/announcements', $this->payload())->assertCreated()->json('data.id');

        $this->actingAs($this->admin)->putJson("/api/announcements/{$id}", $this->payload(['title' => 'Exam week']))->assertOk()->assertJsonPath('data.title', 'Exam week');
        $this->actingAs($this->admin)->postJson("/api/announcements/{$id}/archive")->assertStatus(409);
        $this->actingAs($this->admin)->postJson("/api/announcements/{$id}/publish")->assertOk()->assertJsonPath('data.publish_state', 'published');

        // Published: never rewritten, not deletable, not re-published.
        $this->actingAs($this->admin)->putJson("/api/announcements/{$id}", $this->payload(['title' => 'Changed']))->assertStatus(409);
        $this->actingAs($this->admin)->deleteJson("/api/announcements/{$id}")->assertStatus(409);
        $this->actingAs($this->admin)->postJson("/api/announcements/{$id}/publish")->assertStatus(409);

        $this->actingAs($this->student->user)->getJson("/api/announcements/{$id}")->assertOk();
        $this->actingAs($this->admin)->postJson("/api/announcements/{$id}/archive")->assertOk()->assertJsonPath('data.publish_state', 'archived');
        $this->actingAs($this->student->user)->getJson('/api/announcements/feed')->assertJsonCount(0, 'data');
        $this->actingAs($this->student->user)->getJson("/api/announcements/{$id}")->assertForbidden();

        $draft = $this->actingAs($this->admin)->postJson('/api/announcements', $this->payload())->json('data.id');
        $this->actingAs($this->admin)->deleteJson("/api/announcements/{$draft}")->assertNoContent();
        $this->assertSame(1, Announcement::query()->count());
    }

    public function test_validation(): void
    {
        $as = $this->actingAs($this->admin);

        $as->postJson('/api/announcements', $this->payload(['title' => '']))->assertJsonValidationErrors('title');
        $as->postJson('/api/announcements', $this->payload(['audience_type' => 'planet']))->assertJsonValidationErrors('audience_type');
        $as->postJson('/api/announcements', $this->payload(['audience_type' => 'section', 'audience_id' => null]))->assertJsonValidationErrors('audience_id');
        $as->postJson('/api/announcements', $this->payload(['audience_type' => 'program', 'audience_id' => 999999]))->assertJsonValidationErrors('audience_id');
        $as->postJson('/api/announcements', $this->payload(['announcement_type' => 'gossip']))->assertJsonValidationErrors('announcement_type');
    }

    public function test_lecturers_target_only_what_they_teach(): void
    {
        $as = $this->actingAs($this->lecturer->user);
        $otherSection = Section::factory()->create();

        $as->postJson('/api/announcements', $this->payload(['audience_type' => 'section', 'audience_id' => $this->section->id, 'publish' => true]))->assertCreated();
        $as->postJson('/api/announcements', $this->payload(['audience_type' => 'course', 'audience_id' => $this->section->offering->course_id]))->assertCreated();
        $as->postJson('/api/announcements', $this->payload(['audience_type' => 'section', 'audience_id' => $otherSection->id]))->assertJsonValidationErrors('audience_id');
        $as->postJson('/api/announcements', $this->payload(['audience_type' => 'all']))->assertJsonValidationErrors('audience_type');
        $as->postJson('/api/announcements', $this->payload(['audience_type' => 'department', 'audience_id' => $this->department->id]))->assertJsonValidationErrors('audience_id');

        // A lecturer manages only their own; managers manage everyone's.
        $adminDraft = $this->actingAs($this->admin)->postJson('/api/announcements', $this->payload())->json('data.id');
        $this->actingAs($this->lecturer->user)->getJson('/api/announcements')->assertOk()->assertJsonCount(2, 'data');
        $this->actingAs($this->lecturer->user)->postJson("/api/announcements/{$adminDraft}/publish")->assertForbidden();
        $this->actingAs($this->admin)->getJson('/api/announcements')->assertJsonCount(3, 'data');
    }

    public function test_students_and_department_admin_cannot_write(): void
    {
        $departmentAdmin = User::factory()->create(['role_id' => RoleModel::factory()->withSlug(Role::DepartmentAdmin->value)->create()->id]);

        $this->actingAs($this->student->user)->postJson('/api/announcements', $this->payload())->assertForbidden();
        $this->actingAs($this->student->user)->getJson('/api/announcements')->assertForbidden();
        $this->actingAs($departmentAdmin)->postJson('/api/announcements', $this->payload())->assertForbidden();
        $this->actingAs($departmentAdmin)->getJson('/api/announcements/feed')->assertOk();

        $this->lecturer->update(['is_active' => false]);
        $this->actingAs($this->lecturer->user)->postJson('/api/announcements', $this->payload(['audience_type' => 'section', 'audience_id' => $this->section->id]))->assertForbidden();
    }

    public function test_web_pages_and_student_dashboard(): void
    {
        $this->actingAs($this->admin)->post('/announcements', $this->payload(['audience_type' => 'program', 'audience_id' => $this->program->id, 'publish' => true]))->assertSessionHas('success');

        $this->actingAs($this->admin)->get('/announcements/manage')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Announcements/Manage')->has('announcements.data', 1)->where('canTargetGroups', true)->has('targets.program'));
        $this->actingAs($this->lecturer->user)->get('/announcements/manage')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('canTargetGroups', false)->has('targets.section', 1)->missing('targets.program'));
        $this->actingAs($this->student->user)->get('/announcements/manage')->assertForbidden();

        $this->actingAs($this->student->user)->get('/announcements')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Announcements/Feed')->has('announcements.data', 1)->where('canManage', false));
        $this->actingAs($this->student->user)->get('/dashboard')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Student/Dashboard')->has('dashboard.announcements', 1)->where('dashboard.announcements.0.audience', "Program: {$this->program->name}"));
    }

    public function test_announcement_attachments(): void
    {
        Storage::fake(config('academics.uploads_disk', 's3'));

        $file = UploadedFile::fake()->create('handout.pdf', 300, 'application/pdf');

        $res = $this->actingAs($this->admin)->post('/announcements', [
            ...$this->payload(['audience_type' => 'program', 'audience_id' => $this->program->id, 'publish' => true]),
            'attachments' => [$file],
        ]);
        $res->assertSessionHas('success');

        $announcement = Announcement::query()->where('title', 'Library hours')->firstOrFail();
        $this->assertCount(1, $announcement->attachments);

        $attachment = $announcement->attachments->first();
        $this->assertSame('handout.pdf', $attachment->original_name);
        Storage::disk(config('academics.uploads_disk', 's3'))->assertExists($attachment->storage_key);

        // Student in program can download via web and API
        $downloadRes = $this->actingAs($this->student->user)->get("/announcements/{$announcement->id}/attachments/{$attachment->id}/download");
        $downloadRes->assertOk();

        $apiDownloadRes = $this->actingAs($this->student->user)->get("/api/announcements/{$announcement->id}/attachments/{$attachment->id}/download");
        $apiDownloadRes->assertOk();

        // Outsider student cannot download
        $this->actingAs($this->outsider->user)->get("/announcements/{$announcement->id}/attachments/{$attachment->id}/download")
            ->assertForbidden();
        $this->actingAs($this->outsider->user)->get("/api/announcements/{$announcement->id}/attachments/{$attachment->id}/download")
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return ['title' => 'Library hours', 'body' => 'The library opens until 9 pm during exams.', 'announcement_type' => 'general', 'audience_type' => 'all', 'audience_id' => null, ...$overrides];
    }
}
