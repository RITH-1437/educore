<?php

namespace Tests\Feature\Notifications;

use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Enrollment;
use App\Models\Lecturer;
use App\Models\NotificationPreference;
use App\Models\Room;
use App\Models\ScheduleEntry;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\ClassStartingSoon;
use App\Services\ReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Class-start reminders on Telegram (`docs/48_Class-Reminders-and-Telegram-Linking-Report.md`).
 *
 * The clock is pinned to Tuesday 2026-03-10 00:40 UTC, which is 07:40 in
 * Phnom Penh — the university's clock in these tests.
 */
class ClassRemindersTest extends TestCase
{
    use RefreshDatabase;

    private Semester $semester;

    private Section $section;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Carbon::setTestNow('2026-03-10 00:40:00');
        config(['academics.timezone' => 'Asia/Phnom_Penh', 'academics.class_reminder_minutes' => 30]);

        $this->semester = Semester::factory()->create(['status' => 'open', 'start_date' => '2026-02-01', 'end_date' => '2026-06-30']);
        $this->room = Room::factory()->create(['code' => 'B-204', 'name' => 'Lecture Hall 204']);
        $this->section = $this->section();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_the_section_is_reminded_once_before_class_on_the_university_clock(): void
    {
        $this->meets($this->section, 2, '08:00', '09:30'); // Tuesday, starts in 20 minutes
        $linked = $this->student($this->section, telegram: true);
        $lecturer = $this->lecturer($this->section, telegram: true);

        $this->assertSame(2, app(ReminderService::class)->classesStartingSoon());
        Notification::assertSentTo($linked, ClassStartingSoon::class);
        Notification::assertSentTo($lecturer, ClassStartingSoon::class);

        // A second run (or a doubled scheduler) does not repeat it.
        $this->assertSame(0, app(ReminderService::class)->classesStartingSoon());
        Notification::assertSentToTimes($linked, ClassStartingSoon::class, 1);
    }

    public function test_only_classes_within_the_lead_time_today_are_reminded(): void
    {
        $student = $this->student($this->section, telegram: true);
        $this->meets($this->section, 2, '10:00', '11:00');   // later today
        $this->meets($this->section, 2, '07:30', '07:55');   // already started
        $this->meets($this->section, 3, '08:00', '09:00');   // tomorrow

        $this->assertSame(0, app(ReminderService::class)->classesStartingSoon());

        // Read on UTC (00:40), the 08:00 class would not be due for hours.
        $this->meets($this->section, 2, '08:10', '09:00');
        config(['academics.timezone' => 'UTC']);
        $this->assertSame(0, app(ReminderService::class)->classesStartingSoon());
        config(['academics.timezone' => 'Asia/Phnom_Penh']);
        $this->assertSame(1, app(ReminderService::class)->classesStartingSoon());
        Notification::assertSentTo($student, ClassStartingSoon::class);
    }

    public function test_sections_and_semesters_that_do_not_run_are_skipped(): void
    {
        $draft = $this->section(['status' => 'draft'], code: 'CS211');
        $past = $this->section([], Semester::factory()->create(['status' => 'completed', 'start_date' => '2025-09-01', 'end_date' => '2026-01-15']), 'CS212');
        $this->meets($draft, 2, '08:00', '09:00');
        $this->meets($past, 2, '08:05', '09:00');
        $this->student($draft, telegram: true);
        $this->student($past, telegram: true);

        $this->assertSame(0, app(ReminderService::class)->classesStartingSoon());
        Notification::assertNothingSent();
    }

    public function test_only_people_who_linked_telegram_and_kept_reminders_on_are_reminded(): void
    {
        $this->meets($this->section, 2, '08:00', '09:30');
        $wanted = $this->student($this->section, telegram: true);
        $noTelegram = $this->student($this->section, telegram: false);
        $optedOut = $this->student($this->section, telegram: true, reminders: false);
        $dropped = $this->student($this->section, telegram: true, status: 'dropped');
        $inactive = $this->student($this->section, telegram: true);
        $inactive->update(['is_active' => false]);

        $this->assertSame(1, app(ReminderService::class)->classesStartingSoon());
        Notification::assertSentTo($wanted, ClassStartingSoon::class);
        foreach ([$noTelegram, $optedOut, $dropped, $inactive] as $user) {
            Notification::assertNotSentTo($user, ClassStartingSoon::class);
        }
    }

    public function test_the_reminder_is_telegram_only_and_names_course_time_and_room(): void
    {
        $entry = $this->meets($this->section, 2, '08:00', '09:30');
        $linked = $this->student($this->section, telegram: true);
        $plain = $this->student($this->section, telegram: false);
        $notification = new ClassStartingSoon($entry);

        $this->assertSame([TelegramChannel::class], $notification->via($linked));
        $this->assertSame([], $notification->via($plain));
        $this->assertFalse(method_exists($notification, 'toMail'));
        $this->assertFalse(method_exists($notification, 'toInbox'));

        $text = $notification->toTelegram($linked);
        $this->assertStringContainsString('CS210', $text);
        $this->assertStringContainsString('08:00–09:30', $text);
        $this->assertStringContainsString('B-204', $text);
        $this->assertStringContainsString('/timetable', $text);
    }

    public function test_the_scheduled_command_runs(): void
    {
        $this->meets($this->section, 2, '08:00', '09:30');
        $this->student($this->section, telegram: true);

        $this->artisan('notifications:class-reminders')->expectsOutputToContain('1 class reminder(s) queued.')->assertSuccessful();
    }

    public function test_dashboards_show_the_classes_of_the_university_day(): void
    {
        // 23:30 UTC on Tuesday is 06:30 on Wednesday in Phnom Penh.
        Carbon::setTestNow('2026-03-10 23:30:00');
        $lecturer = Lecturer::factory()->create();
        $this->section->lecturers()->attach($lecturer->id, ['role' => 'primary']);
        $this->meets($this->section, 3, '08:00', '09:30');

        $this->actingAs($lecturer->user)->getJson("/api/lecturers/{$lecturer->id}/dashboard")->assertOk()
            ->assertJsonPath('data.counts.classes_today', 1);

        config(['academics.timezone' => 'UTC']);
        $this->actingAs($lecturer->user)->getJson("/api/lecturers/{$lecturer->id}/dashboard")->assertOk()
            ->assertJsonPath('data.counts.classes_today', 0);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function section(array $attributes = [], ?Semester $semester = null, string $code = 'CS210'): Section
    {
        $offering = CourseOffering::factory()->create([
            'course_id' => Course::factory()->create(['code' => $code, 'name' => 'Data Structures'])->id,
            'semester_id' => ($semester ?? $this->semester)->id,
            'status' => 'open',
        ]);

        return Section::factory()->create(['course_offering_id' => $offering->id, 'status' => 'open', 'code' => 'A', ...$attributes]);
    }

    private function meets(Section $section, int $day, string $start, string $end): ScheduleEntry
    {
        return ScheduleEntry::query()->create(['section_id' => $section->id, 'room_id' => $this->room->id, 'day_of_week' => $day, 'start_time' => $start, 'end_time' => $end]);
    }

    private function student(Section $section, bool $telegram, bool $reminders = true, string $status = 'confirmed'): User
    {
        $student = Student::factory()->create();
        $semester = $section->offering->semester;
        Enrollment::query()->create(['student_id' => $student->id, 'section_id' => $section->id, 'academic_year_id' => $semester->academic_year_id, 'semester_id' => $semester->id, 'status' => $status, 'enrolled_at' => now()]);
        $this->preferences($student->user, $telegram, $reminders);

        return $student->user;
    }

    private function lecturer(Section $section, bool $telegram): User
    {
        $lecturer = Lecturer::factory()->create();
        $section->lecturers()->attach($lecturer->id, ['role' => 'primary']);
        $this->preferences($lecturer->user, $telegram, true);

        return $lecturer->user;
    }

    private function preferences(User $user, bool $telegram, bool $reminders): void
    {
        NotificationPreference::query()->create([
            'user_id' => $user->id,
            'notify_by_telegram' => $telegram,
            'telegram_chat_id' => $telegram ? (string) random_int(100000000, 999999999) : null,
            'class_reminders' => $reminders,
        ]);
    }
}
