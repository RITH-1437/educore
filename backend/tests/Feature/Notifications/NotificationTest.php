<?php

namespace Tests\Feature\Notifications;

use App\Jobs\SendAnnouncementNotifications;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Lecturer;
use App\Models\NotificationPreference;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use App\Notifications\AssignmentDueSoon;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\DocumentRequestUpdated;
use App\Notifications\EnrollmentConfirmed;
use App\Notifications\InvoiceIssued;
use App\Notifications\PaymentRecorded;
use App\Notifications\TestNotification;
use App\Services\AnnouncementService;
use App\Services\DocumentService;
use App\Services\EnrollmentService;
use App\Services\InvoiceService;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Modules 9.20 / 9.21 — preferences, channel rules, event triggers, audience
 * fan-out, Telegram delivery, reminders.
 */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Section $section;

    private Lecturer $lecturer;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-03-04 08:00:00');

        $this->admin = User::factory()->superAdmin()->create();
        $semester = Semester::factory()->create(['status' => 'open', 'start_date' => '2026-02-01', 'end_date' => '2026-06-30']);
        $offering = CourseOffering::factory()->create(['course_id' => Course::factory()->create()->id, 'semester_id' => $semester->id, 'status' => 'open']);
        $this->section = Section::factory()->create(['course_offering_id' => $offering->id, 'status' => 'open', 'capacity' => 30]);
        $this->lecturer = Lecturer::factory()->create();
        $this->section->lecturers()->attach($this->lecturer->id, ['role' => 'primary']);
        $this->student = Student::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ------------------------------------------------------------- channels ---

    public function test_channel_rules_respect_preferences_and_criticality(): void
    {
        $user = $this->student->user;
        $optional = new TestNotification;
        $critical = new InvoiceIssued(app(InvoiceService::class)->create($this->invoicePayload()));

        // Defaults: inbox + email (no chat linked). The in-app inbox cannot be turned off (report 42).
        $this->assertSame(['database', 'mail'], $optional->via($user));

        NotificationPreference::query()->create(['user_id' => $user->id, 'notify_by_email' => false, 'notify_by_telegram' => true, 'telegram_chat_id' => '123456789']);
        $user->refresh();
        $this->assertSame(['database', TelegramChannel::class], $optional->via($user));
        $this->assertSame(['database', 'mail', TelegramChannel::class], $critical->via($user)); // critical ignores the email opt-out

        $user->update(['is_active' => false]);
        $this->assertSame([], $critical->via($user->refresh()));

        $this->assertSame('notifications', $optional->queue);
        $this->assertSame(3, $optional->tries);
    }

    // -------------------------------------------------------------- triggers ---

    public function test_domain_events_notify_the_student(): void
    {
        Notification::fake();
        $this->seed(DocumentTypeSeeder::class);
        $user = $this->student->user;

        $enrollment = app(EnrollmentService::class)->enroll($this->student, $this->section);
        Notification::assertSentTo($user, EnrollmentConfirmed::class, fn ($n) => $n->enrollment->is($enrollment));

        $invoice = app(InvoiceService::class)->create($this->invoicePayload());
        Notification::assertSentTo($user, InvoiceIssued::class);
        $payment = app(InvoiceService::class)->recordPayment($invoice, ['amount' => 50, 'paid_on' => '2026-03-04', 'method' => 'cash'], $this->admin);
        app(InvoiceService::class)->reverse($payment, 'Wrong invoice', $this->admin);
        Notification::assertSentToTimes($user, PaymentRecorded::class, 2);

        $request = app(DocumentService::class)->request($this->student, ['document_type_id' => DocumentType::query()->where('code', 'enrollment_certificate')->value('id')]);
        app(DocumentService::class)->reject($request, $this->admin, 'Incomplete');
        Notification::assertSentTo($user, DocumentRequestUpdated::class, fn ($n) => $n->request->status === DocumentRequest::STATUS_REJECTED);
    }

    public function test_publishing_queues_audience_fan_out(): void
    {
        Bus::fake([SendAnnouncementNotifications::class]);
        $service = app(AnnouncementService::class);

        $draft = $service->create($this->admin, ['title' => 'Draft', 'body' => 'x', 'audience_type' => 'all']);
        Bus::assertNotDispatched(SendAnnouncementNotifications::class);

        $service->publish($draft, $this->admin);
        Bus::assertDispatched(SendAnnouncementNotifications::class, fn ($job) => $job->announcement->is($draft) && $job->queue === 'notifications');
    }

    public function test_fan_out_reaches_exactly_the_audience(): void
    {
        Notification::fake();
        app(EnrollmentService::class)->enroll($this->student, $this->section);
        $outsider = Student::factory()->create();
        $inactive = Student::factory()->create();
        app(EnrollmentService::class)->enroll($inactive, $this->section);
        $inactive->user->update(['is_active' => false]);

        $announcement = app(AnnouncementService::class)->create($this->admin, ['title' => 'Room change', 'body' => 'Lab 2', 'audience_type' => 'section', 'audience_id' => $this->section->id]);
        $announcement->update(['publish_state' => 'published', 'published_at' => now()]);
        (new SendAnnouncementNotifications($announcement))->handle(app(AnnouncementService::class));

        Notification::assertSentTo($this->student->user, AnnouncementPublished::class);
        Notification::assertSentTo($this->lecturer->user, AnnouncementPublished::class);
        Notification::assertNotSentTo([$outsider->user, $inactive->user, $this->admin], AnnouncementPublished::class);
    }

    // -------------------------------------------------------------- telegram ---

    public function test_telegram_channel_calls_the_bot_api_only_when_configured(): void
    {
        $user = $this->student->user;
        NotificationPreference::query()->create(['user_id' => $user->id, 'notify_by_email' => false, 'notify_by_telegram' => true, 'telegram_chat_id' => '987654321']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        // No token configured: skipped without a request.
        config(['services.telegram.bot_token' => null]);
        $user->refresh()->notifyNow(new TestNotification);
        Http::assertNothingSent();

        config(['services.telegram.bot_token' => 'test-token']);
        $user->notifyNow(new TestNotification);
        Http::assertSent(fn (HttpRequest $request) => $request->url() === 'https://api.telegram.org/bottest-token/sendMessage'
            && $request['chat_id'] === '987654321'
            && str_contains($request['text'], 'Telegram notifications are working'));
    }

    public function test_telegram_api_errors_surface_for_retry(): void
    {
        $user = $this->student->user;
        NotificationPreference::query()->create(['user_id' => $user->id, 'notify_by_email' => false, 'notify_by_telegram' => true, 'telegram_chat_id' => '987654321']);
        config(['services.telegram.bot_token' => 'test-token']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false], 400)]);

        // The exception reaches the queue worker, which retries and finally calls failed().
        $this->expectException(RequestException::class);
        $user->refresh()->notifyNow(new TestNotification);
    }

    // ------------------------------------------------------------- reminders ---

    public function test_assignment_reminders_skip_submitted_students(): void
    {
        Notification::fake();
        $enrollment = app(EnrollmentService::class)->enroll($this->student, $this->section);
        $other = Student::factory()->create();
        $otherEnrollment = app(EnrollmentService::class)->enroll($other, $this->section);

        $soon = Assignment::factory()->create(['section_id' => $this->section->id, 'due_at' => now()->addHours(12)]);
        Assignment::factory()->create(['section_id' => $this->section->id, 'due_at' => now()->addDays(3)]);
        Assignment::factory()->draft()->create(['section_id' => $this->section->id, 'due_at' => now()->addHours(6)]);
        AssignmentSubmission::query()->create(['assignment_id' => $soon->id, 'enrollment_id' => $otherEnrollment->id]);

        $this->artisan('notifications:assignment-reminders')->expectsOutputToContain('1 reminder(s) queued')->assertSuccessful();
        Notification::assertSentTo($this->student->user, AssignmentDueSoon::class, fn ($n) => $n->assignment->is($soon));
        Notification::assertNotSentTo($other->user, AssignmentDueSoon::class);
        $this->assertNotNull($enrollment);
    }

    // ----------------------------------------------------------- preferences ---

    public function test_preferences_api_and_page(): void
    {
        Notification::fake();
        $user = $this->student->user;

        $this->actingAs($user)->getJson('/api/notification-preferences')->assertOk()
            ->assertJsonPath('data.notify_by_email', true)->assertJsonPath('data.telegram_chat_id', null)->assertJsonPath('data.telegram_enabled', false);

        $this->actingAs($user)->putJson('/api/notification-preferences', ['notify_by_email' => false, 'notify_by_telegram' => true])->assertJsonValidationErrors('telegram_chat_id');
        $this->actingAs($user)->putJson('/api/notification-preferences', ['notify_by_email' => false, 'notify_by_telegram' => true, 'telegram_chat_id' => 'abc'])->assertJsonValidationErrors('telegram_chat_id');
        $this->actingAs($user)->putJson('/api/notification-preferences', ['notify_by_email' => false, 'notify_by_telegram' => true, 'telegram_chat_id' => '123456789'])
            ->assertOk()->assertJsonPath('data.notify_by_email', false)->assertJsonPath('data.telegram_chat_id', '123456789');
        $this->assertSame(1, NotificationPreference::query()->where('user_id', $user->id)->count());

        $this->actingAs($user)->postJson('/api/notification-preferences/test')->assertStatus(202);
        Notification::assertSentTo($user, TestNotification::class);
        $this->actingAs($user)->postJson('/api/notification-preferences/test');
        $this->actingAs($user)->postJson('/api/notification-preferences/test');
        $this->actingAs($user)->postJson('/api/notification-preferences/test')->assertStatus(429);

        $this->actingAs($this->lecturer->user)->get('/notifications')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Notifications/Preferences')->where('preferences.notify_by_email', true));
        $this->actingAs($this->lecturer->user)->put('/notifications', ['notify_by_email' => true, 'notify_by_telegram' => false, 'telegram_chat_id' => null])->assertSessionHas('success');
    }

    /**
     * @return array<string, mixed>
     */
    private function invoicePayload(): array
    {
        return ['student_id' => $this->student->id, 'title' => 'Tuition', 'due_date' => '2026-04-01', 'items' => [['description' => 'Tuition', 'quantity' => 1, 'unit_price' => 100]]];
    }
}
