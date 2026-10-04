<?php

namespace Tests\Feature\Students;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Lecturer;
use App\Models\ScheduleEntry;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Services\EnrollmentService;
use App\Services\GpaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Module 9.15 — the student academic dashboard aggregates GPA, credits,
 * attendance, today's classes, due work, exams and grades for one student.
 */
class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;

    private Section $section;

    private Enrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();
        // Wednesday (ISO day 3).
        Carbon::setTestNow('2026-03-04 08:00:00');

        $semester = Semester::factory()->create(['status' => 'open', 'start_date' => '2026-02-01', 'end_date' => '2026-06-30']);
        $this->section = $this->newSection($semester, Course::factory()->create(['code' => 'CS201', 'credits' => 4]));
        $this->section->lecturers()->attach(Lecturer::factory()->create()->id, ['role' => 'primary']);
        ScheduleEntry::factory()->create(['section_id' => $this->section->id, 'day_of_week' => 3, 'start_time' => '09:00', 'end_time' => '10:30']);
        ScheduleEntry::factory()->create(['section_id' => $this->section->id, 'day_of_week' => 5, 'start_time' => '09:00', 'end_time' => '10:30']);
        $this->student = Student::factory()->create();
        $this->enrollment = app(EnrollmentService::class)->enroll($this->student, $this->section);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_dashboard_aggregates_the_students_academic_data(): void
    {
        // Attendance: 3 present / late out of 4 counted (excused ignored) → 75 %.
        foreach (['present', 'late', 'present', 'absent', 'excused'] as $i => $status) {
            $session = AttendanceSession::query()->create(['section_id' => $this->section->id, 'session_date' => Carbon::parse('2026-02-02')->addDays($i * 2), 'status' => 'held']);
            AttendanceRecord::query()->create(['attendance_session_id' => $session->id, 'enrollment_id' => $this->enrollment->id, 'status' => $status]);
        }

        // Assignments: one due and open, one already submitted, one past due, one draft.
        $due = Assignment::factory()->create(['section_id' => $this->section->id, 'title' => 'Lab 2', 'due_at' => '2026-03-10 23:59:00']);
        $submitted = Assignment::factory()->create(['section_id' => $this->section->id, 'due_at' => '2026-03-11 23:59:00']);
        AssignmentSubmission::query()->create(['assignment_id' => $submitted->id, 'enrollment_id' => $this->enrollment->id]);
        Assignment::factory()->create(['section_id' => $this->section->id, 'due_at' => '2026-03-01 23:59:00']);
        Assignment::factory()->draft()->create(['section_id' => $this->section->id, 'due_at' => '2026-03-12 23:59:00']);

        // Exams: one upcoming, one past.
        Exam::factory()->create(['section_id' => $this->section->id, 'title' => 'Midterm', 'scheduled_date' => '2026-04-10']);
        Exam::factory()->create(['section_id' => $this->section->id, 'title' => 'Quiz 1', 'exam_type' => 'quiz', 'weight' => 5, 'scheduled_date' => '2026-02-20']);

        // An approved grade from an earlier semester: 3 credits, A.
        $old = Semester::factory()->forYear(AcademicYear::factory()->create(['start_date' => '2024-09-01', 'end_date' => '2025-08-31']))->create(['status' => 'completed']);
        $oldEnrollment = Enrollment::query()->create([
            'student_id' => $this->student->id, 'section_id' => $this->newSection($old, Course::factory()->create(['credits' => 3]))->id,
            'academic_year_id' => $old->academic_year_id, 'semester_id' => $old->id, 'status' => 'completed', 'enrolled_at' => now(),
        ]);
        Grade::query()->create(['enrollment_id' => $oldEnrollment->id, 'letter_grade' => 'A', 'grade_point' => 4, 'status' => 'approved', 'approved_at' => now()]);
        app(GpaService::class)->recalculate($this->student);

        $data = $this->actingAs($this->student->user)->getJson("/api/students/{$this->student->id}/dashboard")->assertOk()->json('data');

        $this->assertSame($this->student->id, $data['student']['id']);
        $this->assertSame($this->section->offering->semester_id, $data['semester']['id']);
        $this->assertEquals(4, $data['gpa']['cumulative']);
        $this->assertEquals(4, $data['credits']['current']);
        $this->assertSame(1, $data['credits']['courses']);
        $this->assertEquals(3, $data['credits']['earned']);
        $this->assertEquals(75, $data['attendance']['rate']);
        $this->assertCount(1, $data['attendance']['courses']);
        $this->assertCount(1, $data['today']);
        $this->assertSame('09:00', $data['today'][0]['start_time']);
        $this->assertSame([$due->id], array_column($data['assignments'], 'id'));
        $this->assertSame(['Midterm'], array_column($data['exams'], 'title'));
        $this->assertSame(['A'], array_column($data['grades'], 'letter_grade'));
    }

    public function test_empty_dashboard_for_a_student_without_enrollments(): void
    {
        $student = Student::factory()->create();

        $this->actingAs($student->user)->getJson("/api/students/{$student->id}/dashboard")->assertOk()
            ->assertJsonPath('data.semester', null)
            ->assertJsonPath('data.gpa.cumulative', null)
            ->assertJsonPath('data.attendance.rate', null)
            ->assertJsonPath('data.credits.current', 0)
            ->assertJsonCount(0, 'data.today')
            ->assertJsonCount(0, 'data.assignments');
    }

    public function test_no_classes_today_outside_the_semester(): void
    {
        Carbon::setTestNow('2026-07-08 08:00:00'); // a Wednesday after the semester ended

        $this->actingAs($this->student->user)->getJson("/api/students/{$this->student->id}/dashboard")->assertOk()->assertJsonCount(0, 'data.today');
    }

    public function test_access_is_limited_to_staff_and_the_student(): void
    {
        $other = Student::factory()->create();
        $departmentAdmin = $this->departmentAdminFor(Department::factory()->create());
        $this->placeInDepartment($this->student, $departmentAdmin->department_id);

        $this->actingAs($other->user)->getJson("/api/students/{$this->student->id}/dashboard")->assertForbidden();
        $this->actingAs(Lecturer::factory()->create()->user)->getJson("/api/students/{$this->student->id}/dashboard")->assertForbidden();
        $this->actingAs($departmentAdmin)->getJson("/api/students/{$this->student->id}/dashboard")->assertOk();
        $this->actingAs($this->departmentAdminFor(Department::factory()->create()))->getJson("/api/students/{$this->student->id}/dashboard")->assertForbidden();
        $this->actingAs(User::factory()->superAdmin()->create())->getJson("/api/students/{$this->student->id}/dashboard")->assertOk();
    }

    public function test_student_dashboard_page(): void
    {
        $this->actingAs($this->student->user)->get('/dashboard')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Student/Dashboard')
                ->where('dashboard.student.id', $this->student->id)
                ->where('dashboard.credits.current', 4)
                ->has('dashboard.today', 1));

        // A student account without a profile keeps the generic workspace page.
        $orphan = User::factory()->create(['role_id' => $this->student->user->role_id]);
        $this->actingAs($orphan)->get('/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page->component('RoleDashboard'));
    }

    private function newSection(Semester $semester, Course $course): Section
    {
        $offering = CourseOffering::factory()->create(['course_id' => $course->id, 'semester_id' => $semester->id, 'status' => 'open']);

        return Section::factory()->create(['course_offering_id' => $offering->id, 'status' => 'open', 'capacity' => 30]);
    }
}
