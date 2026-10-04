<?php

namespace Tests\Feature\Timetable;

use App\Enums\Role;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Lecturer;
use App\Models\Role as RoleModel;
use App\Models\Room;
use App\Models\ScheduleEntry;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Services\EnrollmentService;
use Database\Seeders\AcademicYearSeeder;
use Database\Seeders\CourseOfferingSeeder;
use Database\Seeders\CourseSeeder;
use Database\Seeders\LecturerSeeder;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\RoomSeeder;
use Database\Seeders\ScheduleSeeder;
use Database\Seeders\UniversityStructureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Module 9.10 — rooms, weekly schedules and every conflict rule.
 */
class TimetableTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Semester $semester;

    private Room $room;

    private Section $a;

    private Section $b;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->superAdmin()->create();
        $this->semester = Semester::factory()->create(['status' => 'open']);
        $this->room = Room::factory()->create(['code' => 'A-101', 'capacity' => 50]);
        $this->a = $this->section('CS101');
        $this->b = $this->section('MA101');
    }

    // ---------------------------------------------------------------- rooms ---

    public function test_room_crud_roles_and_delete_guard(): void
    {
        $departmentAdmin = User::factory()->create(['role_id' => RoleModel::factory()->withSlug(Role::DepartmentAdmin->value)->create()->id]);

        $this->actingAs($departmentAdmin)->getJson('/api/rooms')->assertOk();
        $this->actingAs($departmentAdmin)->postJson('/api/rooms', $this->roomPayload())->assertForbidden();

        $id = $this->actingAs($this->admin)->postJson('/api/rooms', $this->roomPayload(['code' => 'LAB-1', 'room_type' => 'lab']))
            ->assertCreated()->assertJsonPath('data.room_type', 'lab')->json('data.id');
        $this->actingAs($this->admin)->postJson('/api/rooms', $this->roomPayload(['code' => 'LAB-1', 'capacity' => 0, 'room_type' => 'gym']))
            ->assertUnprocessable()->assertJsonValidationErrors(['code', 'capacity', 'room_type']);
        $this->actingAs($this->admin)->putJson("/api/rooms/{$id}", $this->roomPayload(['code' => 'LAB-1', 'is_active' => false]))
            ->assertOk()->assertJsonPath('data.is_active', false);
        $this->actingAs($this->admin)->deleteJson("/api/rooms/{$id}")->assertNoContent();

        $this->add($this->a, 1, '08:00', '09:30')->assertCreated();
        $this->actingAs($this->admin)->deleteJson("/api/rooms/{$this->room->id}")->assertStatus(409);

        $this->actingAs($this->admin)->get('/rooms')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Rooms/Index')->has('rooms.data', 1));
    }

    // ------------------------------------------------------- slot validation ---

    public function test_slot_shape_and_bounds(): void
    {
        $this->add($this->a, 8, '08:00', '09:00')->assertUnprocessable()->assertJsonValidationErrors(['day_of_week']);
        $this->add($this->a, 1, '10:00', '09:00')->assertUnprocessable()->assertJsonValidationErrors(['end_time']);
        $this->add($this->a, 1, '05:00', '07:00')->assertUnprocessable()->assertJsonValidationErrors(['start_time']);
        $this->add($this->a, 1, '8am', '09:00')->assertUnprocessable()->assertJsonValidationErrors(['start_time']);

        $inactive = Room::factory()->create(['is_active' => false]);
        $this->add($this->a, 1, '08:00', '09:00', $inactive)->assertUnprocessable()->assertJsonValidationErrors(['room_id']);
    }

    public function test_room_conflict_including_partial_overlap(): void
    {
        $this->add($this->a, 1, '10:00', '12:00')->assertCreated();

        $this->add($this->b, 1, '11:00', '13:00')->assertUnprocessable()->assertJsonValidationErrors(['room_id']); // partial
        $this->add($this->b, 1, '10:30', '11:30')->assertUnprocessable(); // inside
        $this->add($this->b, 1, '12:00', '13:00')->assertCreated(); // touching is fine
        $this->add($this->b, 2, '10:00', '12:00')->assertCreated(); // other day is fine
    }

    public function test_same_room_in_another_semester_is_not_a_conflict(): void
    {
        $this->add($this->a, 1, '10:00', '12:00')->assertCreated();
        $other = $this->section('PH101', Semester::factory()->create(['status' => 'open']));

        $this->add($other, 1, '10:00', '12:00')->assertCreated();
    }

    public function test_section_cannot_meet_twice_at_once(): void
    {
        $this->add($this->a, 1, '10:00', '12:00')->assertCreated();

        $this->add($this->a, 1, '11:00', '12:30', Room::factory()->create())->assertUnprocessable()->assertJsonValidationErrors(['start_time']);
    }

    public function test_lecturer_conflict_both_when_scheduling_and_when_assigning(): void
    {
        $lecturer = Lecturer::factory()->create();
        $this->a->lecturers()->attach($lecturer->id, ['role' => 'primary']);
        $this->add($this->a, 1, '10:00', '12:00')->assertCreated();

        // Section B, taught by the same lecturer, cannot overlap…
        $this->b->lecturers()->attach($lecturer->id, ['role' => 'primary']);
        $this->add($this->b, 1, '11:00', '13:00', Room::factory()->create())->assertUnprocessable()->assertJsonValidationErrors(['start_time']);
        $this->b->lecturers()->detach($lecturer->id);

        // …and once B is scheduled at that time, the lecturer cannot be assigned to it.
        $this->add($this->b, 1, '11:00', '13:00', Room::factory()->create())->assertCreated();
        $this->actingAs($this->admin)->postJson("/api/sections/{$this->b->id}/lecturers", ['lecturer_id' => $lecturer->id])
            ->assertUnprocessable()->assertJsonValidationErrors(['lecturer_id']);
    }

    public function test_student_conflicts_when_scheduling_and_when_enrolling(): void
    {
        $student = Student::factory()->create();
        $this->add($this->a, 1, '10:00', '12:00')->assertCreated();
        app(EnrollmentService::class)->enroll($student, $this->a);

        // B overlapping A: enrolling the student in B is refused…
        $this->add($this->b, 1, '11:00', '13:00', Room::factory()->create())->assertCreated();
        $this->actingAs($this->admin)->postJson('/api/enrollments', ['student_id' => $student->id, 'section_id' => $this->b->id])
            ->assertUnprocessable()->assertJsonValidationErrors(['section_id']);

        // …and moving A onto B's other slot is refused once a shared student exists.
        $c = $this->section('EE101');
        $this->add($c, 3, '08:00', '09:00', Room::factory()->create())->assertCreated();
        app(EnrollmentService::class)->enroll($student, $c);
        $this->add($this->a, 3, '08:30', '09:30', Room::factory()->create())->assertUnprocessable()->assertJsonValidationErrors(['start_time']);
    }

    public function test_room_must_seat_current_enrollment(): void
    {
        foreach (range(1, 3) as $i) {
            app(EnrollmentService::class)->enroll(Student::factory()->create(), $this->a);
        }

        $this->add($this->a, 1, '08:00', '09:00', Room::factory()->create(['capacity' => 2]))
            ->assertUnprocessable()->assertJsonValidationErrors(['room_id']);
    }

    public function test_completed_semester_is_locked_and_entries_can_move_and_be_removed(): void
    {
        $id = $this->add($this->a, 1, '08:00', '09:00')->json('data.id');

        $this->actingAs($this->admin)->putJson("/api/schedule-entries/{$id}", ['room_id' => $this->room->id, 'day_of_week' => 1, 'start_time' => '08:30', 'end_time' => '09:30'])
            ->assertOk()->assertJsonPath('data.start_time', '08:30');
        $this->actingAs($this->admin)->getJson("/api/sections/{$this->a->id}/schedule")->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($this->admin)->deleteJson("/api/schedule-entries/{$id}")->assertNoContent();

        $this->semester->update(['status' => 'completed']);
        $this->add($this->a, 1, '08:00', '09:00')->assertStatus(409);
    }

    // ---------------------------------------------------------- timetables ---

    public function test_personal_timetables_and_scoping(): void
    {
        $student = Student::factory()->create();
        $lecturer = Lecturer::factory()->create();
        $this->a->lecturers()->attach($lecturer->id, ['role' => 'primary']);
        $this->add($this->a, 2, '09:00', '10:30')->assertCreated();
        app(EnrollmentService::class)->enroll($student, $this->a);

        $this->actingAs($student->user)->getJson("/api/timetable/student/{$student->id}")
            ->assertOk()->assertJsonPath('data.0.day', 'Tuesday')->assertJsonPath('data.0.course.code', $this->a->offering->course->code)->assertJsonPath('data.0.room.code', 'A-101');
        $this->actingAs($student->user)->getJson('/api/timetable/student/'.Student::factory()->create()->id)->assertForbidden();
        $this->actingAs($lecturer->user)->getJson("/api/timetable/lecturer/{$lecturer->id}")->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($student->user)->get('/timetable')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Timetable/Index')->where('owner', 'student')->has('entries', 1));
        $this->actingAs($lecturer->user)->get('/timetable')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Timetable/Index')->where('owner', 'lecturer'));
        $this->actingAs($this->admin)->get('/timetable')->assertForbidden();
    }

    public function test_seeders_build_a_conflict_free_timetable_idempotently(): void
    {
        foreach ([UniversityStructureSeeder::class, ProgramSeeder::class, CourseSeeder::class, LecturerSeeder::class, AcademicYearSeeder::class, CourseOfferingSeeder::class, RoomSeeder::class] as $seeder) {
            $this->seed($seeder);
        }

        $this->seed(ScheduleSeeder::class);
        $count = ScheduleEntry::query()->count();
        $this->seed(ScheduleSeeder::class);

        $this->assertGreaterThan(0, $count);
        $this->assertSame($count, ScheduleEntry::query()->count());
        $this->assertSame(Section::query()->whereIn('status', ['open', 'active'])->count() * 2, $count);
    }

    // ------------------------------------------------------------- helpers ---

    private function section(string $code, ?Semester $semester = null): Section
    {
        $offering = CourseOffering::factory()->create([
            'course_id' => Course::factory()->create(['code' => $code.fake()->unique()->numerify('##')])->id,
            'semester_id' => ($semester ?? $this->semester)->id,
            'status' => 'open',
        ]);

        return Section::factory()->create(['course_offering_id' => $offering->id, 'code' => 'A', 'status' => 'open', 'capacity' => 40]);
    }

    private function add(Section $section, int $day, string $start, string $end, ?Room $room = null): TestResponse
    {
        return $this->actingAs($this->admin)->postJson("/api/sections/{$section->id}/schedule", [
            'room_id' => ($room ?? $this->room)->id,
            'day_of_week' => $day,
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function roomPayload(array $overrides = []): array
    {
        return ['code' => 'R-'.fake()->unique()->numerify('###'), 'name' => 'Room', 'capacity' => 40, 'room_type' => 'lecture', ...$overrides];
    }
}
