<?php

namespace Tests\Feature\Announcements;

use App\Jobs\SendAnnouncementNotifications;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\Program;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Notifications\AnnouncementScheduleFailed;
use App\Services\AnnouncementService;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Report 51 — Department Admin authoring, scheduled publishing and read
 * receipts on top of module 9.19.
 */
class AnnouncementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Department $department;

    private Program $program;

    private Course $course;

    private Section $section;

    private Lecturer $lecturer;

    private Student $student;

    private Student $outsider;

    private User $departmentAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->superAdmin()->create();
        $this->department = Department::factory()->create();
        $this->program = Program::factory()->create(['department_id' => $this->department->id]);
        $this->course = Course::factory()->create(['department_id' => $this->department->id]);

        $semester = Semester::factory()->create(['status' => 'open']);
        $offering = CourseOffering::factory()->create(['course_id' => $this->course->id, 'semester_id' => $semester->id, 'status' => 'open']);
        $this->section = Section::factory()->create(['course_offering_id' => $offering->id, 'status' => 'open', 'capacity' => 30]);
        $this->lecturer = Lecturer::factory()->create(['department_id' => $this->department->id]);
        $this->section->lecturers()->attach($this->lecturer->id, ['role' => 'primary']);

        $this->student = Student::factory()->inProgram($this->program)->create();
        app(EnrollmentService::class)->enroll($this->student, $this->section);
        $this->outsider = Student::factory()->inProgram()->create();
        $this->departmentAdmin = $this->departmentAdminFor($this->department);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // -------------------------------------------------- Department Admin authoring

    public function test_a_department_admin_writes_to_their_own_department_only(): void
    {
        $as = $this->actingAs($this->departmentAdmin);

        foreach (['department' => $this->department->id, 'program' => $this->program->id, 'course' => $this->course->id, 'section' => $this->section->id] as $type => $id) {
            $as->postJson('/api/announcements', $this->payload(['audience_type' => $type, 'audience_id' => $id]))->assertCreated();
        }

        $other = Department::factory()->create();
        $as->postJson('/api/announcements', $this->payload(['audience_type' => 'department', 'audience_id' => $other->id]))->assertJsonValidationErrors('audience_id');
        $as->postJson('/api/announcements', $this->payload(['audience_type' => 'program', 'audience_id' => $this->outsider->currentProgram->program_id]))->assertJsonValidationErrors('audience_id');
        $as->postJson('/api/announcements', $this->payload(['audience_type' => 'section', 'audience_id' => Section::factory()->create()->id]))->assertJsonValidationErrors('audience_id');
        $as->postJson('/api/announcements', $this->payload(['audience_type' => 'all']))
            ->assertJsonValidationErrors(['audience_type' => 'Department Admins can announce only to their department and its programs, courses and sections.']);

        // Their own only; another author's draft is not theirs to publish.
        $adminDraft = $this->actingAs($this->admin)->postJson('/api/announcements', $this->payload())->json('data.id');
        $this->actingAs($this->departmentAdmin)->getJson('/api/announcements')->assertOk()->assertJsonCount(4, 'data');
        $this->actingAs($this->departmentAdmin)->postJson("/api/announcements/{$adminDraft}/publish")->assertForbidden();

        // The compose page offers only their department's targets.
        $this->actingAs($this->departmentAdmin)->get('/announcements/manage')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Announcements/Manage')
                ->where('canTargetGroups', false)
                ->has('targets.department', 1)
                ->where('targets.department.0.id', $this->department->id)
                ->has('targets.program', 1)
                ->has('targets.course', 1)
                ->has('targets.section', 1));

        // Without a department: no writing at all.
        $this->actingAs($this->departmentAdminFor(null))->get('/announcements/manage')->assertForbidden();
    }

    public function test_a_department_audience_includes_its_department_admins(): void
    {
        $id = $this->actingAs($this->departmentAdmin)
            ->postJson('/api/announcements', $this->payload(['audience_type' => 'department', 'audience_id' => $this->department->id, 'publish' => true]))
            ->assertCreated()->json('data.id');

        $this->actingAs($this->departmentAdmin)->getJson('/api/announcements/feed')->assertJsonPath('data.0.id', $id);
        $this->actingAs($this->student->user)->getJson('/api/announcements/feed')->assertJsonPath('data.0.id', $id);
        $this->actingAs($this->departmentAdminFor(Department::factory()->create()))->getJson('/api/announcements/feed')->assertJsonCount(0, 'data');

        $recipients = app(AnnouncementService::class)->recipients(Announcement::query()->findOrFail($id))->pluck('id');
        $this->assertTrue($recipients->contains($this->departmentAdmin->id));
        $this->assertTrue($recipients->contains($this->student->user_id));
        $this->assertTrue($recipients->contains($this->lecturer->user_id));
    }

    // ---------------------------------------------------------- scheduling

    public function test_a_scheduled_announcement_is_published_when_due(): void
    {
        Bus::fake([SendAnnouncementNotifications::class]);
        config(['academics.timezone' => 'Asia/Phnom_Penh']);
        Carbon::setTestNow('2026-10-13 08:00:00'); // 15:00 in Phnom Penh

        $id = $this->actingAs($this->admin)->postJson('/api/announcements', $this->payload(['audience_type' => 'program', 'audience_id' => $this->program->id]))->json('data.id');

        // A time without an offset is the university's wall clock.
        $this->actingAs($this->admin)->postJson("/api/announcements/{$id}/schedule", ['scheduled_for' => '2026-10-13T16:30'])
            ->assertOk()
            ->assertJsonPath('data.publish_state', 'scheduled')
            ->assertJsonPath('data.scheduled_for', '2026-10-13T09:30:00+00:00');

        // Scheduled: not in any feed, not editable, not schedulable again.
        $this->actingAs($this->student->user)->getJson('/api/announcements/feed')->assertJsonCount(0, 'data');
        $this->actingAs($this->admin)->putJson("/api/announcements/{$id}", $this->payload(['title' => 'Changed']))->assertStatus(409);
        $this->actingAs($this->admin)->postJson("/api/announcements/{$id}/schedule", ['scheduled_for' => '2026-10-14T08:00'])->assertStatus(409);

        $this->artisan('announcements:publish-scheduled')->expectsOutputToContain('0 scheduled announcement(s) published')->assertSuccessful();

        Carbon::setTestNow('2026-10-13 09:31:00');
        $this->artisan('announcements:publish-scheduled')->expectsOutputToContain('1 scheduled announcement(s) published')->assertSuccessful();

        $announcement = Announcement::query()->findOrFail($id);
        $this->assertSame(Announcement::STATE_PUBLISHED, $announcement->publish_state);
        $this->assertSame('2026-10-13 09:31:00', $announcement->published_at->utc()->toDateTimeString());
        $this->assertSame('2026-10-13 09:30:00', $announcement->scheduled_for->utc()->toDateTimeString());
        Bus::assertDispatched(SendAnnouncementNotifications::class, fn ($job) => $job->announcement->is($announcement));
        $this->assertTrue(AuditLog::query()->where('action', 'announcement.scheduled')->exists());
        $this->assertTrue(AuditLog::query()->where('action', 'announcement.published')->exists());
        $this->actingAs($this->student->user)->getJson('/api/announcements/feed')->assertJsonPath('data.0.id', $id);

        // Run again: nothing left to publish.
        $this->artisan('announcements:publish-scheduled')->expectsOutputToContain('0 scheduled announcement(s) published');
    }

    public function test_schedule_rules_unschedule_and_publish_now(): void
    {
        Carbon::setTestNow('2026-10-13 08:00:00');

        // Created already scheduled (an offset given: taken as is).
        $id = $this->actingAs($this->admin)->postJson('/api/announcements', $this->payload(['scheduled_for' => '2026-10-20T08:00:00+00:00']))
            ->assertCreated()->assertJsonPath('data.publish_state', 'scheduled')->json('data.id');
        $this->actingAs($this->admin)->postJson('/api/announcements', $this->payload(['scheduled_for' => '2026-10-20T08:00:00+00:00', 'publish' => true]))
            ->assertJsonValidationErrors('scheduled_for');

        $draft = $this->actingAs($this->admin)->postJson('/api/announcements', $this->payload())->json('data.id');
        $this->actingAs($this->admin)->postJson("/api/announcements/{$draft}/schedule", ['scheduled_for' => '2026-10-13T07:59:00+00:00'])->assertJsonValidationErrors('scheduled_for');
        $this->actingAs($this->admin)->postJson("/api/announcements/{$draft}/schedule", ['scheduled_for' => '2027-11-01T08:00:00+00:00'])->assertJsonValidationErrors('scheduled_for');
        $this->actingAs($this->admin)->postJson("/api/announcements/{$draft}/schedule", [])->assertJsonValidationErrors('scheduled_for');
        $this->actingAs($this->admin)->postJson("/api/announcements/{$draft}/unschedule")->assertStatus(409);

        // Back to draft, then publish now.
        $this->actingAs($this->admin)->postJson("/api/announcements/{$id}/unschedule")->assertOk()
            ->assertJsonPath('data.publish_state', 'draft')->assertJsonPath('data.scheduled_for', null);
        $this->actingAs($this->admin)->postJson("/api/announcements/{$id}/schedule", ['scheduled_for' => '2026-10-21T08:00:00+00:00'])->assertOk();
        $this->actingAs($this->admin)->postJson("/api/announcements/{$id}/publish")->assertOk()->assertJsonPath('data.publish_state', 'published');
        $this->actingAs($this->admin)->postJson("/api/announcements/{$id}/unschedule")->assertStatus(409);

        // Students never schedule; the web form works like the API.
        $this->actingAs($this->student->user)->postJson("/api/announcements/{$draft}/schedule", ['scheduled_for' => '2026-10-21T08:00:00+00:00'])->assertForbidden();
        $this->actingAs($this->admin)->post("/announcements/{$draft}/schedule", ['scheduled_for' => '2026-10-21T08:00:00+00:00'])->assertSessionHas('success', 'Announcement scheduled.');
        $this->actingAs($this->admin)->post("/announcements/{$draft}/unschedule")->assertSessionHas('success', 'Back to drafts.');
    }

    public function test_a_scheduled_announcement_whose_author_lost_access_returns_to_draft(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-10-13 08:00:00');

        $id = $this->actingAs($this->lecturer->user)
            ->postJson('/api/announcements', $this->payload(['audience_type' => 'section', 'audience_id' => $this->section->id, 'scheduled_for' => '2026-10-13T09:00:00+00:00']))
            ->assertCreated()->json('data.id');

        // The lecturer no longer teaches the section when it falls due.
        $this->section->lecturers()->detach($this->lecturer->id);
        Carbon::setTestNow('2026-10-13 09:05:00');
        $this->artisan('announcements:publish-scheduled')->expectsOutputToContain('0 scheduled announcement(s) published');

        $announcement = Announcement::query()->findOrFail($id);
        $this->assertSame(Announcement::STATE_DRAFT, $announcement->publish_state);
        $this->assertNull($announcement->scheduled_for);
        $this->assertTrue(AuditLog::query()->where('action', 'announcement.schedule_failed')->exists());
        Notification::assertSentTo($this->lecturer->user, AnnouncementScheduleFailed::class, fn ($n) => $n->announcement->is($announcement)
            && $n->toInbox($this->lecturer->user)['url'] === '/announcements/manage');
        $this->actingAs($this->student->user)->getJson('/api/announcements/feed')->assertJsonCount(0, 'data');
    }

    // --------------------------------------------------------- read receipts

    public function test_members_mark_announcements_read_and_authors_see_the_count(): void
    {
        $id = $this->actingAs($this->admin)->postJson('/api/announcements', $this->payload(['audience_type' => 'program', 'audience_id' => $this->program->id, 'publish' => true]))->json('data.id');

        $this->actingAs($this->student->user)->getJson('/api/announcements/feed')
            ->assertJsonPath('data.0.is_read', false)->assertJsonPath('meta.unread', 1);

        $this->actingAs($this->student->user)->postJson("/api/announcements/{$id}/read")->assertNoContent();
        $this->actingAs($this->student->user)->postJson("/api/announcements/{$id}/read")->assertNoContent(); // idempotent
        $this->assertSame(1, AnnouncementRead::query()->count());

        $this->actingAs($this->student->user)->getJson('/api/announcements/feed')
            ->assertJsonPath('data.0.is_read', true)->assertJsonPath('meta.unread', 0);

        // Only a published announcement in your own feed.
        $this->actingAs($this->outsider->user)->postJson("/api/announcements/{$id}/read")->assertForbidden();
        $draft = $this->actingAs($this->admin)->postJson('/api/announcements', $this->payload())->json('data.id');
        $this->actingAs($this->student->user)->postJson("/api/announcements/{$draft}/read")->assertForbidden();

        // The author sees how many of the current audience read it.
        $this->actingAs($this->admin)->getJson('/api/announcements?filters[publish_state]=published')
            ->assertJsonPath('data.0.id', $id)
            ->assertJsonPath('data.0.audience_size', 1)
            ->assertJsonPath('data.0.read_count', 1);

        // Mark all: everything unread in the feed, nothing else.
        foreach ([1, 2] as $n) {
            $this->actingAs($this->admin)->postJson('/api/announcements', $this->payload(['title' => "Notice {$n}", 'audience_type' => 'students', 'publish' => true]));
        }
        $this->actingAs($this->student->user)->postJson('/api/announcements/read-all')->assertOk()->assertJsonPath('data.marked', 2);
        $this->actingAs($this->student->user)->postJson('/api/announcements/read-all')->assertJsonPath('data.marked', 0);
        $this->actingAs($this->outsider->user)->getJson('/api/announcements/feed')->assertJsonPath('meta.unread', 2);
        $this->assertSame(3, AnnouncementRead::query()->where('user_id', $this->student->user_id)->count());
    }

    public function test_web_feed_shows_read_state_and_marks_it(): void
    {
        $id = $this->actingAs($this->admin)->postJson('/api/announcements', $this->payload(['audience_type' => 'students', 'publish' => true]))->json('data.id');

        $this->actingAs($this->student->user)->get('/announcements')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Announcements/Feed')->where('unreadCount', 1)->where('announcements.data.0.is_read', false));

        $this->actingAs($this->student->user)->post("/announcements/{$id}/read")->assertRedirect();
        $this->actingAs($this->student->user)->get('/announcements')
            ->assertInertia(fn (Assert $page) => $page->where('unreadCount', 0)->where('announcements.data.0.is_read', true));

        $this->actingAs($this->outsider->user)->post('/announcements/read-all')->assertSessionHas('success', '1 announcement marked read.');

        $this->actingAs($this->admin)->get('/announcements/manage')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('announcements.data.0.read_count', 2)->has('announcements.data.0.audience_size'));
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
