<?php

namespace Tests\Feature\Offerings;

use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\Room;
use App\Models\ScheduleEntry;
use App\Models\Section;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * A Department Admin manages the offerings, sections, lecturer assignments and
 * weekly class times of their own department's courses, and nothing of another
 * department's (`docs/46_Department-Admin-Sections-and-Schedules-Report.md`).
 */
class DepartmentAdminSectionManagementTest extends TestCase
{
    use RefreshDatabase;

    private Department $mine;

    private Department $theirs;

    private User $departmentAdmin;

    private Semester $semester;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mine = Department::factory()->create();
        $this->theirs = Department::factory()->create();
        $this->departmentAdmin = $this->departmentAdminFor($this->mine);
        $this->semester = Semester::factory()->open()->create();
        $this->room = Room::factory()->create(['capacity' => 60]);
    }

    public function test_offers_their_departments_course_but_not_anothers(): void
    {
        $as = $this->actingAs($this->departmentAdmin);

        $as->postJson('/api/offerings', $this->offeringPayload($this->course($this->mine)))
            ->assertCreated()->assertJsonPath('data.semester.id', $this->semester->id);
        $as->post('/offerings', $this->offeringPayload($this->course($this->mine)))
            ->assertRedirect()->assertSessionHas('success');

        $as->postJson('/api/offerings', $this->offeringPayload($this->course($this->theirs)))->assertForbidden();
        $as->post('/offerings', $this->offeringPayload($this->course($this->theirs)))->assertForbidden();
        $this->assertSame(2, CourseOffering::query()->count());
    }

    public function test_updates_and_deletes_only_their_departments_offerings(): void
    {
        $own = $this->offering($this->mine);
        $other = $this->offering($this->theirs);
        $as = $this->actingAs($this->departmentAdmin);

        $as->patchJson("/api/offerings/{$own->id}", ['status' => 'open', 'max_enrollments' => 70])
            ->assertOk()->assertJsonPath('data.max_enrollments', 70);
        $as->put("/offerings/{$own->id}", ['status' => 'published'])->assertRedirect();
        $this->assertSame('published', $own->fresh()->status);

        $as->patchJson("/api/offerings/{$other->id}", ['status' => 'open'])->assertForbidden();
        $as->deleteJson("/api/offerings/{$other->id}")->assertForbidden();

        $as->deleteJson("/api/offerings/{$own->id}")->assertNoContent();
        $this->assertModelExists($other);
    }

    public function test_manages_sections_of_their_departments_offerings_only(): void
    {
        $own = $this->offering($this->mine);
        $other = $this->offering($this->theirs);
        $otherSection = Section::factory()->create(['course_offering_id' => $other->id]);
        $as = $this->actingAs($this->departmentAdmin);

        $sectionId = $as->postJson("/api/offerings/{$own->id}/sections", ['code' => 'A', 'capacity' => 30])
            ->assertCreated()->json('data.id');
        $as->post("/offerings/{$own->id}/sections", ['code' => 'B', 'capacity' => 25])->assertRedirect();
        $as->putJson("/api/sections/{$sectionId}", ['code' => 'A', 'name' => 'Morning', 'capacity' => 35, 'status' => 'open'])
            ->assertOk()->assertJsonPath('data.capacity', 35);
        $as->deleteJson("/api/sections/{$sectionId}")->assertNoContent();
        $this->assertSame(1, $own->sections()->count());

        $as->postJson("/api/offerings/{$other->id}/sections", ['code' => 'C', 'capacity' => 30])->assertForbidden();
        $as->putJson("/api/sections/{$otherSection->id}", ['code' => 'Z', 'capacity' => 35, 'status' => 'open'])->assertForbidden();
        $as->deleteJson("/api/sections/{$otherSection->id}")->assertForbidden();
        $as->delete("/sections/{$otherSection->id}")->assertForbidden();
    }

    public function test_assigns_only_lecturers_of_their_department(): void
    {
        $section = $this->section($this->mine);
        $ownLecturer = Lecturer::factory()->create(['department_id' => $this->mine->id]);
        $otherLecturer = Lecturer::factory()->create(['department_id' => $this->theirs->id]);
        $as = $this->actingAs($this->departmentAdmin);

        $as->postJson("/api/sections/{$section->id}/lecturers", ['lecturer_id' => $otherLecturer->id])
            ->assertUnprocessable()->assertJsonValidationErrors(['lecturer_id' => 'Choose a lecturer from your department.']);
        $as->postJson("/api/sections/{$section->id}/lecturers", ['lecturer_id' => $ownLecturer->id, 'role' => 'primary'])
            ->assertCreated()->assertJsonPath('data.lecturers.0.id', $ownLecturer->id);
        $as->deleteJson("/api/sections/{$section->id}/lecturers/{$ownLecturer->id}")->assertNoContent();

        // Another department's section stays out of reach even with their own lecturer.
        $as->postJson("/api/sections/{$this->section($this->theirs)->id}/lecturers", ['lecturer_id' => $ownLecturer->id])->assertForbidden();

        // A manager may still assign across departments.
        $this->actingAs(User::factory()->superAdmin()->create())
            ->postJson("/api/sections/{$section->id}/lecturers", ['lecturer_id' => $otherLecturer->id])->assertCreated();
    }

    public function test_schedules_class_times_of_their_departments_sections_only(): void
    {
        $section = $this->section($this->mine);
        $otherSection = $this->section($this->theirs);
        $as = $this->actingAs($this->departmentAdmin);

        $entryId = $as->postJson("/api/sections/{$section->id}/schedule", $this->slot(1, '08:00', '09:30'))
            ->assertCreated()->json('data.id');
        $as->putJson("/api/schedule-entries/{$entryId}", $this->slot(2, '10:00', '11:30'))
            ->assertOk()->assertJsonPath('data.day_of_week', 2);
        $as->post("/sections/{$section->id}/schedule", $this->slot(3, '08:00', '09:30'))->assertRedirect()->assertSessionHas('success');

        // The usual timetable rules still apply: the room is taken at that time.
        $otherEntry = ScheduleEntry::query()->findOrFail($this->actingAs(User::factory()->superAdmin()->create())
            ->postJson("/api/sections/{$otherSection->id}/schedule", $this->slot(4, '13:00', '14:30'))->assertCreated()->json('data.id'));
        $as = $this->actingAs($this->departmentAdmin);
        $as->postJson("/api/sections/{$section->id}/schedule", $this->slot(4, '13:30', '15:00'))->assertUnprocessable();

        $as->postJson("/api/sections/{$otherSection->id}/schedule", $this->slot(5, '08:00', '09:00'))->assertForbidden();
        $as->putJson("/api/schedule-entries/{$otherEntry->id}", $this->slot(5, '08:00', '09:00'))->assertForbidden();
        $as->deleteJson("/api/schedule-entries/{$otherEntry->id}")->assertForbidden();
        $as->delete("/schedule-entries/{$otherEntry->id}")->assertForbidden();

        $as->deleteJson("/api/schedule-entries/{$entryId}")->assertNoContent();
    }

    public function test_rooms_stay_with_managers(): void
    {
        $this->actingAs($this->departmentAdmin)->postJson('/api/rooms', ['code' => 'X-1', 'name' => 'X', 'capacity' => 10, 'room_type' => 'lecture'])->assertForbidden();
        $this->actingAs($this->departmentAdmin)->deleteJson("/api/rooms/{$this->room->id}")->assertForbidden();
    }

    public function test_unassigned_department_admin_manages_nothing(): void
    {
        $unassigned = $this->departmentAdminFor(null);
        $own = $this->offering($this->mine);

        $this->actingAs($unassigned)->postJson('/api/offerings', $this->offeringPayload($this->course($this->mine)))->assertForbidden();
        $this->actingAs($unassigned)->patchJson("/api/offerings/{$own->id}", ['status' => 'open'])->assertForbidden();
        $this->actingAs($unassigned)->get('/offerings')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('canManage', false));
    }

    public function test_screens_offer_the_controls_and_only_their_departments_lecturers(): void
    {
        $own = $this->offering($this->mine);
        $ownLecturer = Lecturer::factory()->create(['department_id' => $this->mine->id]);
        Lecturer::factory()->create(['department_id' => $this->theirs->id]);

        $this->actingAs($this->departmentAdmin)->get('/offerings')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Offerings/Index')->where('canManage', true));
        $this->actingAs($this->departmentAdmin)->get("/offerings/{$own->id}")->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Offerings/Show')
                ->where('canManage', true)
                ->has('lecturers', 1)
                ->where('lecturers.0.id', $ownLecturer->id)
                ->has('rooms', 1));

        // Managers keep every lecturer on the form.
        $this->actingAs(User::factory()->superAdmin()->create())->get("/offerings/{$own->id}")
            ->assertInertia(fn (Assert $page) => $page->where('canManage', true)->has('lecturers', 2));
    }

    private function course(Department $department): Course
    {
        return Course::factory()->create(['department_id' => $department->id]);
    }

    private function offering(Department $department): CourseOffering
    {
        return CourseOffering::factory()->create(['course_id' => $this->course($department)->id, 'semester_id' => $this->semester->id, 'status' => 'open']);
    }

    private function section(Department $department): Section
    {
        return Section::factory()->create(['course_offering_id' => $this->offering($department)->id, 'status' => 'open']);
    }

    /**
     * @return array<string, mixed>
     */
    private function offeringPayload(Course $course): array
    {
        return ['course_id' => $course->id, 'semester_id' => $this->semester->id, 'status' => 'draft'];
    }

    /**
     * @return array<string, mixed>
     */
    private function slot(int $day, string $start, string $end): array
    {
        return ['room_id' => $this->room->id, 'day_of_week' => $day, 'start_time' => $start, 'end_time' => $end];
    }
}
