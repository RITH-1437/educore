<?php

namespace Tests\Feature\Lecturers;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceSession;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Faculty;
use App\Models\Grade;
use App\Models\Lecturer;
use App\Models\Role as RoleModel;
use App\Models\Room;
use App\Models\ScheduleEntry;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Lecturer dashboard (`docs/35_Lecturer-Dashboard-Report.md`): today's
 * classes, registers not yet taken, submissions to grade, upcoming exams and
 * grade-sheet progress of the lecturer's own sections in the current semester.
 */
class LecturerDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Lecturer $lecturer;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-03-04 12:00:00'); // a Wednesday

        $this->lecturer = Lecturer::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_dashboard_counts_the_lecturers_own_teaching_work(): void
    {
        $semester = Semester::factory()->create(['status' => 'open', 'start_date' => '2026-02-01', 'end_date' => '2026-06-30']);
        $mine = $this->section('CS101', $semester, [1, 3], 'primary'); // meets today (Wednesday)
        $assisting = $this->section('MA101', $semester, [2], 'assistant');
        $theirs = $this->section('PH101', $semester, [5], 'primary', Lecturer::factory()->create());
        $this->section('EE101', Semester::factory()->create(['status' => 'planned', 'start_date' => '2026-09-01']), [1], 'primary'); // another semester: not shown

        // Registers: CS101 met 10 times (Mondays and Wednesdays from 2 Feb), 2 taken; MA101 met 5 Tuesdays, none taken.
        foreach (['2026-03-02', '2026-03-04'] as $date) {
            AttendanceSession::query()->create(['section_id' => $mine->id, 'session_date' => $date, 'status' => 'held']);
        }

        // Submissions: two of three still to grade in CS101; another lecturer's are not counted.
        $enrollments = collect(range(1, 3))->map(fn () => Enrollment::factory()->create(['student_id' => Student::factory()->create()->id, 'section_id' => $mine->id]));
        $assignment = Assignment::factory()->create(['section_id' => $mine->id]);
        foreach (['submitted', 'late', 'graded'] as $index => $status) {
            AssignmentSubmission::query()->create(['assignment_id' => $assignment->id, 'enrollment_id' => $enrollments[$index]->id, 'status' => $status, 'score' => $status === 'graded' ? 80 : null]);
        }
        $other = Enrollment::factory()->create(['student_id' => Student::factory()->create()->id, 'section_id' => $theirs->id]);
        AssignmentSubmission::query()->create(['assignment_id' => Assignment::factory()->create(['section_id' => $theirs->id])->id, 'enrollment_id' => $other->id, 'status' => 'submitted']);

        // Grade sheet: one draft out of three students.
        Grade::query()->create(['enrollment_id' => $enrollments[0]->id, 'status' => Grade::STATUS_DRAFT, 'total_score' => 75]);

        // Exams: one upcoming in CS101; past ones and other lecturers' are not upcoming.
        Exam::factory()->create(['section_id' => $mine->id, 'title' => 'Midterm', 'scheduled_date' => '2026-03-10', 'is_published' => true]);
        Exam::factory()->create(['section_id' => $assisting->id, 'scheduled_date' => '2026-02-20']);
        Exam::factory()->create(['section_id' => $theirs->id, 'scheduled_date' => '2026-03-12']);

        $counts = ['classes_today' => 1, 'registers_to_take' => 13, 'submissions_to_grade' => 2, 'upcoming_exams' => 1];

        $this->actingAs($this->lecturer->user)->get('/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Lecturer/Dashboard')
            ->where('dashboard.semester.id', $semester->id)
            ->where('dashboard.counts', $counts)
            ->has('dashboard.today', 1)
            ->where('dashboard.today.0.section.id', $mine->id)
            ->has('dashboard.exams', 1)
            ->where('dashboard.exams.0.title', 'Midterm')
            ->where('dashboard.exams.0.course.code', 'CS101')
            ->has('dashboard.sections', 2)
            ->where('dashboard.sections.0.course.code', 'CS101')
            ->where('dashboard.sections.0.students', 3)
            ->where('dashboard.sections.0.registers_to_take', 8)
            ->where('dashboard.sections.0.submissions_to_grade', 2)
            ->where('dashboard.sections.0.grades', 'draft')
            ->where('dashboard.sections.1.role', 'assistant')
            ->where('dashboard.sections.1.registers_to_take', 5)
            ->where('dashboard.sections.1.grades', 'no_students'));

        // The API answers the same to the lecturer and to staff who may view them.
        $this->actingAs($this->lecturer->user)->getJson("/api/lecturers/{$this->lecturer->id}/dashboard")->assertOk()->assertJsonPath('data.counts', $counts);
        $this->actingAs($this->userWithRole('university-admin'))->getJson("/api/lecturers/{$this->lecturer->id}/dashboard")->assertOk()->assertJsonPath('data.counts', $counts);
    }

    public function test_access_and_empty_states(): void
    {
        $this->getJson("/api/lecturers/{$this->lecturer->id}/dashboard")->assertUnauthorized();

        // Another lecturer, a student, and another faculty's Faculty Admin may not read it.
        $this->actingAs(Lecturer::factory()->create()->user)->getJson("/api/lecturers/{$this->lecturer->id}/dashboard")->assertForbidden();
        $this->actingAs($this->userWithRole('student'))->getJson("/api/lecturers/{$this->lecturer->id}/dashboard")->assertForbidden();
        $this->actingAs($this->facultyAdminFor(Faculty::factory()->create()))->getJson("/api/lecturers/{$this->lecturer->id}/dashboard")->assertForbidden();

        // No semester yet: nothing to count.
        $this->actingAs($this->lecturer->user)->get('/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Lecturer/Dashboard')
            ->where('dashboard.semester', null)
            ->where('dashboard.counts', ['classes_today' => 0, 'registers_to_take' => 0, 'submissions_to_grade' => 0, 'upcoming_exams' => 0])
            ->has('dashboard.sections', 0));

        // A lecturer account without a profile gets the page without data.
        $this->actingAs($this->userWithRole('lecturer'))->get('/dashboard')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Lecturer/Dashboard')->where('dashboard', null));
    }

    /**
     * @param  list<int>  $days  ISO weekdays the section meets
     */
    private function section(string $code, Semester $semester, array $days, string $role, ?Lecturer $lecturer = null): Section
    {
        $offering = CourseOffering::factory()->create(['course_id' => Course::factory()->create(['code' => $code])->id, 'semester_id' => $semester->id, 'status' => 'open']);
        $section = Section::factory()->create(['course_offering_id' => $offering->id, 'code' => 'A', 'status' => 'open']);
        $section->lecturers()->attach(($lecturer ?? $this->lecturer)->id, ['role' => $role]);

        $room = Room::factory()->create();
        foreach ($days as $day) {
            ScheduleEntry::factory()->create(['section_id' => $section->id, 'room_id' => $room->id, 'day_of_week' => $day, 'start_time' => '08:00', 'end_time' => '09:30']);
        }

        return $section;
    }

    private function userWithRole(string $slug): User
    {
        $role = RoleModel::query()->firstWhere('slug', $slug) ?? RoleModel::factory()->withSlug($slug)->create();

        return User::factory()->create(['role_id' => $role->id]);
    }
}
