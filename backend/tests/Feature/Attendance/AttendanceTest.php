<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceSession;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Lecturer;
use App\Models\Room;
use App\Models\ScheduleEntry;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Module 9.11 — attendance recording rules, derived rates, scoping.
 *
 * Time is frozen on a Wednesday so "today" and the section's Monday/Wednesday
 * schedule are deterministic.
 */
class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private const MONDAY = '2026-03-02';

    private const WEDNESDAY = '2026-03-04';

    private User $admin;

    private Section $section;

    private Lecturer $lecturer;

    /** @var list<Enrollment> */
    private array $enrollments = [];

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-03-04 12:00:00');

        $this->admin = User::factory()->superAdmin()->create();
        $semester = Semester::factory()->create(['status' => 'open', 'start_date' => '2026-02-01', 'end_date' => '2026-06-30']);
        $offering = CourseOffering::factory()->create(['course_id' => Course::factory()->create()->id, 'semester_id' => $semester->id, 'status' => 'open']);
        $this->section = Section::factory()->create(['course_offering_id' => $offering->id, 'code' => 'A', 'status' => 'open', 'capacity' => 40]);

        $room = Room::factory()->create();
        ScheduleEntry::factory()->create(['section_id' => $this->section->id, 'room_id' => $room->id, 'day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '09:30']);
        ScheduleEntry::factory()->create(['section_id' => $this->section->id, 'room_id' => $room->id, 'day_of_week' => 3, 'start_time' => '08:00', 'end_time' => '09:30']);

        $this->lecturer = Lecturer::factory()->create();
        $this->section->lecturers()->attach($this->lecturer->id, ['role' => 'primary']);

        foreach (range(1, 3) as $i) {
            $this->enrollments[] = app(EnrollmentService::class)->enroll(Student::factory()->create(), $this->section);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ------------------------------------------------------------- recording ---

    public function test_lecturer_records_bulk_attendance_and_times_come_from_the_schedule(): void
    {
        $this->actingAs($this->lecturer->user)->postJson("/api/sections/{$this->section->id}/attendance", $this->payload(self::MONDAY, ['present', 'late', 'absent']))
            ->assertOk()
            ->assertJsonPath('data.session.status', 'held')
            ->assertJsonPath('data.session.start_time', '08:00')
            ->assertJsonCount(3, 'data.roster');

        $this->assertDatabaseCount('attendance_records', 3);
        $this->assertSame($this->lecturer->user_id, AttendanceSession::query()->first()->recorded_by);
    }

    public function test_saving_again_updates_and_partial_saves_leave_others_untouched(): void
    {
        $this->record(self::MONDAY, ['present', 'present', 'present']);

        $this->actingAs($this->lecturer->user)->postJson("/api/sections/{$this->section->id}/attendance", [
            'session_date' => self::MONDAY,
            'records' => [['enrollment_id' => $this->enrollments[0]->id, 'status' => 'absent']],
        ])->assertOk()
            ->assertJsonPath('data.roster.*.status', fn ($statuses) => collect($statuses)->sort()->values()->all() === ['absent', 'present', 'present']);

        $this->assertSame(1, AttendanceSession::query()->count());
        $this->assertDatabaseCount('attendance_records', 3);
    }

    public function test_date_policy(): void
    {
        $this->record('2026-03-09', ['present', 'present', 'present'])->assertUnprocessable()->assertJsonValidationErrors(['session_date']); // future
        $this->record('2026-03-03', ['present', 'present', 'present'])->assertUnprocessable()->assertJsonValidationErrors(['session_date']); // Tuesday: not scheduled
        $this->record('2026-01-26', ['present', 'present', 'present'])->assertUnprocessable()->assertJsonValidationErrors(['session_date']); // before semester
        $this->record('03/02/2026', ['present', 'present', 'present'])->assertUnprocessable()->assertJsonValidationErrors(['session_date']);

        $this->section->offering->semester->update(['status' => 'completed']);
        $this->record(self::MONDAY, ['present', 'present', 'present'])->assertStatus(409);
    }

    public function test_only_this_sections_markable_enrollments_are_accepted(): void
    {
        $dropped = app(EnrollmentService::class)->drop($this->enrollments[2]);
        $other = Enrollment::factory()->create();

        foreach ([$dropped->id, $other->id] as $id) {
            $this->actingAs($this->lecturer->user)->postJson("/api/sections/{$this->section->id}/attendance", [
                'session_date' => self::MONDAY,
                'records' => [['enrollment_id' => $id, 'status' => 'present']],
            ])->assertUnprocessable()->assertJsonValidationErrors(['records']);
        }

        $this->actingAs($this->lecturer->user)->postJson("/api/sections/{$this->section->id}/attendance", [
            'session_date' => self::MONDAY,
            'records' => [['enrollment_id' => $this->enrollments[0]->id, 'status' => 'sleeping']],
        ])->assertUnprocessable()->assertJsonValidationErrors(['records.0.status']);
    }

    // ---------------------------------------------------------------- rates ---

    public function test_rate_is_derived_with_late_counted_and_excused_cancelled_excluded(): void
    {
        $this->record('2026-02-02', ['present', 'absent', 'excused']);
        $this->record('2026-02-04', ['late', 'absent', 'present']);
        $this->record('2026-02-09', ['absent', 'absent', 'present']);
        $cancelled = $this->record('2026-02-11', ['absent', 'absent', 'absent'])->json('data.session.id');
        $this->actingAs($this->lecturer->user)->postJson("/api/attendance-sessions/{$cancelled}/cancel")->assertOk()->assertJsonPath('data.status', 'cancelled');

        $summary = collect($this->actingAs($this->admin)->getJson("/api/sections/{$this->section->id}/attendance/summary")->assertOk()->json('data'))->keyBy('enrollment_id');

        // 1: present, late, absent → 2/3; 2: three absents → 0; 3: excused, present, present → 2/2.
        $this->assertSame(66.7, $summary[$this->enrollments[0]->id]['rate']);
        $this->assertSame(0, $summary[$this->enrollments[1]->id]['rate'] * 1);
        $this->assertSame(100, (int) $summary[$this->enrollments[2]->id]['rate']);
        $this->assertSame(1, $summary[$this->enrollments[2]->id]['excused']);

        // Recording into a cancelled session is refused until it is restored.
        $this->record('2026-02-11', ['present', 'present', 'present'])->assertStatus(409);
        $this->actingAs($this->lecturer->user)->postJson("/api/attendance-sessions/{$cancelled}/cancel", ['cancelled' => false])->assertOk()->assertJsonPath('data.status', 'held');
    }

    public function test_rate_is_null_before_anything_counts(): void
    {
        $this->actingAs($this->admin)->getJson("/api/sections/{$this->section->id}/attendance/summary")
            ->assertOk()->assertJsonPath('data.0.rate', null);
    }

    // -------------------------------------------------------- authorization ---

    public function test_who_may_record_and_read(): void
    {
        $otherLecturer = Lecturer::factory()->create();
        $departmentAdmin = $this->departmentAdminFor($this->departmentOfSection($this->section));
        $student = $this->enrollments[0]->student;

        $this->actingAs($otherLecturer->user)->postJson("/api/sections/{$this->section->id}/attendance", $this->payload(self::MONDAY, ['present', 'present', 'present']))->assertForbidden();
        $this->actingAs($departmentAdmin)->postJson("/api/sections/{$this->section->id}/attendance", $this->payload(self::MONDAY, ['present', 'present', 'present']))->assertForbidden();
        $this->actingAs($student->user)->postJson("/api/sections/{$this->section->id}/attendance", $this->payload(self::MONDAY, ['present', 'present', 'present']))->assertForbidden();
        $this->actingAs($this->admin)->postJson("/api/sections/{$this->section->id}/attendance", $this->payload(self::MONDAY, ['present', 'present', 'present']))->assertOk();

        $this->actingAs($departmentAdmin)->getJson("/api/sections/{$this->section->id}/attendance?date=".self::MONDAY)->assertOk();
        $this->actingAs($this->departmentAdminFor(Department::factory()->create()))->getJson("/api/sections/{$this->section->id}/attendance?date=".self::MONDAY)->assertForbidden();
        $this->actingAs($otherLecturer->user)->getJson("/api/sections/{$this->section->id}/attendance/summary")->assertForbidden();

        $this->actingAs($student->user)->getJson("/api/students/{$student->id}/attendance")->assertOk()->assertJsonPath('data.0.present', 1);
        $this->actingAs($student->user)->getJson('/api/students/'.$this->enrollments[1]->student_id.'/attendance')->assertForbidden();

        // An inactive lecturer loses recording rights.
        $this->lecturer->update(['is_active' => false]);
        $this->record(self::WEDNESDAY, ['present', 'present', 'present'])->assertForbidden();
    }

    // ----------------------------------------------------------------- web ---

    public function test_web_pages(): void
    {
        $this->record(self::MONDAY, ['present', 'late', 'absent']);

        $this->actingAs($this->lecturer->user)->get('/attendance')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Attendance/Classes')->has('sections', 1)->where('sections.0.students', 3));

        $this->actingAs($this->lecturer->user)->get("/attendance/sections/{$this->section->id}?date=".self::MONDAY)->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Attendance/Section')
                ->where('canRecord', true)
                ->has('roster', 3)
                ->has('summary', 3)
                ->where('expectedDates.0.date', self::WEDNESDAY)); // newest first

        $this->actingAs($this->lecturer->user)->post("/attendance/sections/{$this->section->id}", $this->payload(self::WEDNESDAY, ['present', 'present', 'present']))
            ->assertSessionHas('success');

        $student = $this->enrollments[0]->student;
        $this->actingAs($student->user)->get('/my-attendance')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Attendance/Mine')->has('summary', 1)->where('summary.0.present', 2));
        $this->actingAs($student->user)->get("/attendance/sections/{$this->section->id}")->assertForbidden();
    }

    public function test_drop_after_attendance_becomes_withdrawal(): void
    {
        $this->record(self::MONDAY, ['present', 'present', 'present']);

        $this->actingAs($this->admin)->deleteJson("/api/enrollments/{$this->enrollments[0]->id}")->assertOk()->assertJsonPath('data.status', 'withdrawn');
    }

    // ------------------------------------------------------------- helpers ---

    /**
     * @param  list<string>  $statuses  one per enrollment, in order
     * @return array<string, mixed>
     */
    private function payload(string $date, array $statuses): array
    {
        return [
            'session_date' => $date,
            'records' => array_map(fn (Enrollment $e, string $status) => ['enrollment_id' => $e->id, 'status' => $status], $this->enrollments, $statuses),
        ];
    }

    /**
     * @param  list<string>  $statuses
     */
    private function record(string $date, array $statuses): TestResponse
    {
        return $this->actingAs($this->lecturer->user)->postJson("/api/sections/{$this->section->id}/attendance", $this->payload($date, $statuses));
    }
}
