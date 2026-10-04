<?php

namespace Tests\Feature\Grades;

use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\GpaRecord;
use App\Models\Grade;
use App\Models\Lecturer;
use App\Models\Role as RoleModel;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Notifications\GradePublished;
use App\Services\EnrollmentService;
use App\Services\GpaService;
use App\Services\GradingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Module 9.14 — grading scale, course weights, grade computation and
 * workflow, GPA correctness and recomputation, authorization.
 */
class GradingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Semester $semester;

    private Section $section;

    private Lecturer $lecturer;

    private Student $alice;

    private Student $bob;

    private Enrollment $aliceEnrollment;

    private Enrollment $bobEnrollment;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-06-20 12:00:00');

        app(GradingService::class)->saveScale(GradingService::DEFAULT_BANDS);

        $this->admin = User::factory()->superAdmin()->create();
        $this->semester = Semester::factory()->create(['status' => 'open', 'start_date' => '2026-02-01', 'end_date' => '2026-06-30']);
        $this->section = $this->newSection($this->semester, Course::factory()->create(['credits' => 3]));
        $this->lecturer = Lecturer::factory()->create();
        $this->section->lecturers()->attach($this->lecturer->id, ['role' => 'primary']);
        $this->alice = Student::factory()->create(['first_name' => 'Alice', 'last_name' => 'A']);
        $this->bob = Student::factory()->create(['first_name' => 'Bob', 'last_name' => 'B']);
        $this->aliceEnrollment = app(EnrollmentService::class)->enroll($this->alice, $this->section);
        $this->bobEnrollment = app(EnrollmentService::class)->enroll($this->bob, $this->section);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // -------------------------------------------------------- computation ---

    public function test_sheet_weights_available_components_and_maps_letters(): void
    {
        $this->seedScores();

        // No attendance session and no practical exam: those weights drop out
        // and assignment 25 + midterm 20 + final 40 = 85 is scaled to 100.
        // Alice (90·25 + 80·20 + 90·40) / 85 = 87.65 → A; Bob (0 + 40·20 + 50·40) / 85 = 32.94 → F.
        $response = $this->actingAs($this->lecturer->user)->getJson("/api/sections/{$this->section->id}/grades")
            ->assertOk()
            ->assertJsonPath('components.attendance', false)
            ->assertJsonPath('components.practical', false)
            ->assertJsonPath('components.midterm', true)
            ->assertJsonPath('weights.final', 40);

        $rows = collect($response->json('data'))->keyBy('enrollment_id');
        $this->assertSame(87.65, $rows[$this->aliceEnrollment->id]['computed']['total']);
        $this->assertSame('A', $rows[$this->aliceEnrollment->id]['computed']['letter']);
        $this->assertEquals(90, $rows[$this->aliceEnrollment->id]['components']['assignment']);
        $this->assertSame(32.94, $rows[$this->bobEnrollment->id]['computed']['total']);
        $this->assertSame('F', $rows[$this->bobEnrollment->id]['computed']['letter']);
        $this->assertEquals(0, $rows[$this->bobEnrollment->id]['components']['assignment']);
    }

    public function test_attendance_and_course_weights_feed_the_total(): void
    {
        $this->seedScores();
        $session = AttendanceSession::query()->create(['section_id' => $this->section->id, 'session_date' => '2026-03-02', 'status' => 'held']);
        AttendanceRecord::query()->create(['attendance_session_id' => $session->id, 'enrollment_id' => $this->aliceEnrollment->id, 'status' => 'present']);
        AttendanceRecord::query()->create(['attendance_session_id' => $session->id, 'enrollment_id' => $this->bobEnrollment->id, 'status' => 'absent']);

        // Custom weights: attendance 50, final 50, the rest 0.
        $this->actingAs($this->admin)->putJson("/api/courses/{$this->section->offering->course_id}/grading-config", [
            'attendance_weight' => 50, 'assignment_weight' => 0, 'midterm_weight' => 0, 'final_weight' => 50, 'practical_weight' => 0,
        ])->assertOk()->assertJsonPath('data.is_default', false);

        $rows = collect($this->actingAs($this->admin)->getJson("/api/sections/{$this->section->id}/grades")->json('data'))->keyBy('enrollment_id');
        $this->assertSame(95.0, (float) $rows[$this->aliceEnrollment->id]['computed']['total']); // (100 + 90) / 2
        $this->assertSame(25.0, (float) $rows[$this->bobEnrollment->id]['computed']['total']);   // (0 + 50) / 2
    }

    public function test_grading_config_validation_and_access(): void
    {
        $course = $this->section->offering->course_id;
        $url = "/api/courses/{$course}/grading-config";

        $this->actingAs($this->admin)->getJson($url)->assertOk()->assertJsonPath('data.is_default', true)->assertJsonPath('data.final_weight', 40);
        $this->actingAs($this->admin)->putJson($url, ['attendance_weight' => 10, 'assignment_weight' => 10, 'midterm_weight' => 10, 'final_weight' => 10, 'practical_weight' => 10])
            ->assertUnprocessable()->assertJsonValidationErrors('weights');
        $this->actingAs($this->admin)->putJson($url, ['attendance_weight' => -10, 'assignment_weight' => 30, 'midterm_weight' => 20, 'final_weight' => 50, 'practical_weight' => 10])
            ->assertJsonValidationErrors('attendance_weight');

        $this->actingAs($this->lecturer->user)->putJson($url, ['attendance_weight' => 20, 'assignment_weight' => 20, 'midterm_weight' => 20, 'final_weight' => 20, 'practical_weight' => 20])->assertForbidden();
        $this->actingAs($this->lecturer->user)->getJson($url)->assertForbidden();
    }

    // ------------------------------------------------------------ workflow ---

    public function test_compute_submit_approve_and_return(): void
    {
        Notification::fake();
        $this->seedScores();
        $base = "/api/sections/{$this->section->id}/grades";

        // Nothing to submit before drafts exist.
        $this->actingAs($this->lecturer->user)->postJson("{$base}/submit")->assertStatus(409);

        $this->actingAs($this->lecturer->user)->postJson($base, ['remarks' => [['enrollment_id' => $this->aliceEnrollment->id, 'remarks' => 'Excellent']]])
            ->assertOk()->assertJsonPath('saved', 2)->assertJsonPath('counts.draft', 2);
        $this->assertSame('Excellent', Grade::query()->where('enrollment_id', $this->aliceEnrollment->id)->value('remarks'));

        $this->actingAs($this->lecturer->user)->postJson("{$base}/submit")->assertOk()->assertJsonPath('counts.submitted', 2);

        // Submitted grades are not recomputed, and the lecturer cannot approve.
        $this->actingAs($this->lecturer->user)->postJson($base)->assertOk()->assertJsonPath('saved', 0);
        $this->actingAs($this->lecturer->user)->postJson("{$base}/approve")->assertForbidden();

        $this->actingAs($this->admin)->postJson("{$base}/approve")->assertOk()->assertJsonPath('counts.approved', 2);
        $this->assertSame(Enrollment::STATUS_COMPLETED, $this->aliceEnrollment->refresh()->status);
        // Each student is told their grade is out (module 9.20).
        Notification::assertSentTo([$this->alice->user, $this->bob->user], GradePublished::class);
        $this->assertEquals(4.0, GpaRecord::query()->where('student_id', $this->alice->id)->where('cumulative', true)->value('gpa_value'));
        $this->assertEquals(0, GpaRecord::query()->where('student_id', $this->bob->id)->where('cumulative', false)->value('earned_credits'));

        // Return to draft: GPA rows disappear (no approved grades left).
        $this->actingAs($this->admin)->postJson("{$base}/return")->assertOk()->assertJsonPath('counts.draft', 2);
        $this->assertSame(0, GpaRecord::query()->where('student_id', $this->alice->id)->count());
        $this->actingAs($this->admin)->postJson("{$base}/return")->assertStatus(409);
    }

    public function test_submit_requires_a_grade_for_every_student(): void
    {
        $base = "/api/sections/{$this->section->id}/grades";

        // No scores at all: drafts are written but have no letter.
        $this->actingAs($this->lecturer->user)->postJson($base)->assertOk()->assertJsonPath('saved', 2);
        $this->actingAs($this->lecturer->user)->postJson("{$base}/submit")->assertUnprocessable()->assertJsonValidationErrors('grades');

        // A student enrolling after the drafts were computed is missing.
        $this->seedScores();
        $this->actingAs($this->lecturer->user)->postJson($base)->assertOk();
        app(EnrollmentService::class)->enroll(Student::factory()->create(), $this->section);
        $this->actingAs($this->lecturer->user)->postJson("{$base}/submit")->assertUnprocessable()->assertJsonValidationErrors('grades');
    }

    public function test_approved_failing_grade_blocks_prerequisites(): void
    {
        $this->seedScores();
        $base = "/api/sections/{$this->section->id}/grades";
        $this->actingAs($this->lecturer->user)->postJson($base);
        $this->actingAs($this->lecturer->user)->postJson("{$base}/submit");
        $this->actingAs($this->admin)->postJson("{$base}/approve")->assertOk();

        $next = Course::factory()->create();
        $next->prerequisites()->attach($this->section->offering->course_id, ['is_strict' => true]);
        $enrollments = app(EnrollmentService::class);

        $this->assertSame([], $enrollments->missingPrerequisites($this->alice, $next->load('prerequisites')));
        $this->assertCount(1, $enrollments->missingPrerequisites($this->bob, $next->load('prerequisites')));

        // Returned to draft: Alice has not passed yet either.
        $this->actingAs($this->admin)->postJson("{$base}/return")->assertOk();
        $this->assertCount(1, $enrollments->missingPrerequisites($this->alice, $next->load('prerequisites')));
    }

    // ----------------------------------------------------------------- GPA ---

    public function test_gpa_is_credit_weighted_with_retakes_and_recomputed_on_credit_change(): void
    {
        // Codes outside the factory's 1950–2099 range so they can never collide.
        $y1 = AcademicYear::factory()->create(['code' => '1940-1941', 'start_date' => '1940-09-01', 'end_date' => '1941-08-31']);
        $y2 = AcademicYear::factory()->create(['code' => '1941-1942', 'start_date' => '1941-09-01', 'end_date' => '1942-08-31']);
        $s1 = Semester::factory()->forYear($y1)->create(['sequence' => 1, 'status' => 'completed']);
        $s2 = Semester::factory()->forYear($y2)->create(['sequence' => 1, 'status' => 'completed']);
        $p = Course::factory()->create(['credits' => 3]);
        $q = Course::factory()->create(['credits' => 2]);
        $r = Course::factory()->create(['credits' => 4]);
        $student = $this->alice;

        $this->approvedGrade($student, $s1, $p, 'A', 4.0);
        $this->approvedGrade($student, $s1, $q, 'F', 0.0);
        $this->approvedGrade($student, $s2, $q, 'B', 3.0); // retake replaces the F cumulatively
        $this->approvedGrade($student, $s2, $r, 'C+', 2.5);
        // A draft never counts.
        Grade::query()->create(['enrollment_id' => $this->aliceEnrollment->id, 'letter_grade' => 'F', 'grade_point' => 0, 'status' => 'draft']);

        app(GpaService::class)->recalculate($student);

        $gpa = $this->actingAs($student->user)->getJson("/api/students/{$student->id}/gpa")->assertOk()->json('data');
        $this->assertCount(2, $gpa['semesters']);
        $this->assertSame(2.4, $gpa['semesters'][0]['gpa']);          // (4·3 + 0·2) / 5
        $this->assertEquals(3, $gpa['semesters'][0]['earned_credits']);
        $this->assertSame(2.667, $gpa['semesters'][1]['gpa']);        // (3·2 + 2.5·4) / 6
        $this->assertSame(3.111, $gpa['cumulative']['gpa']);          // (12 + 6 + 10) / 9
        $this->assertEquals(9, $gpa['cumulative']['attempted_credits']);
        $this->assertSame(2.4, (float) GpaRecord::query()->where('student_id', $student->id)->where('academic_year_id', $y1->id)->where('cumulative', true)->value('gpa_value'));

        // Credit change recomputes: R 4 → 2 credits.
        $this->actingAs($this->admin)->putJson("/api/courses/{$r->id}", ['department_id' => $r->department_id, 'code' => $r->code, 'name' => $r->name, 'credits' => 2])->assertOk();
        $gpa = $this->actingAs($this->admin)->getJson("/api/students/{$student->id}/grades")->assertOk()->assertJsonCount(4, 'data')->json('gpa');
        $this->assertSame(2.75, $gpa['semesters'][1]['gpa']);         // (6 + 5) / 4
        $this->assertSame(3.286, $gpa['cumulative']['gpa']);          // (12 + 6 + 5) / 7
    }

    public function test_student_without_grades_has_no_gpa(): void
    {
        $this->actingAs($this->bob->user)->getJson("/api/students/{$this->bob->id}/gpa")
            ->assertOk()->assertJsonPath('data.cumulative', null)->assertJsonCount(0, 'data.semesters');
    }

    // --------------------------------------------------------------- scale ---

    public function test_scale_read_and_replace(): void
    {
        $this->actingAs($this->bob->user)->getJson('/api/grading-scale')->assertOk()
            ->assertJsonPath('name', 'Standard')->assertJsonPath('data.0.grade', 'A')->assertJsonPath('data.0.max_percentage', 100)
            ->assertJsonPath('data.1.max_percentage', 84.99);

        $bands = [['grade' => 'P', 'min_percentage' => 50, 'grade_point' => 4], ['grade' => 'F', 'min_percentage' => 0, 'grade_point' => 0]];
        $this->actingAs($this->lecturer->user)->putJson('/api/grading-scale', ['bands' => $bands])->assertForbidden();
        $this->actingAs($this->admin)->putJson('/api/grading-scale', ['bands' => $bands])->assertOk()
            ->assertJsonCount(2, 'data')->assertJsonPath('data.1.max_percentage', 49.99)->assertJsonPath('data.1.is_pass', false);

        $this->actingAs($this->admin)->putJson('/api/grading-scale', ['bands' => [['grade' => 'A', 'min_percentage' => 50, 'grade_point' => 4], ['grade' => 'B', 'min_percentage' => 10, 'grade_point' => 3]]])
            ->assertJsonValidationErrors('bands');
        $this->actingAs($this->admin)->putJson('/api/grading-scale', ['bands' => [['grade' => 'A', 'min_percentage' => 50, 'grade_point' => 1], ['grade' => 'F', 'min_percentage' => 0, 'grade_point' => 2]]])
            ->assertJsonValidationErrors('bands');
        $this->actingAs($this->admin)->putJson('/api/grading-scale', ['bands' => [['grade' => 'A', 'min_percentage' => 0, 'grade_point' => 4], ['grade' => 'a', 'min_percentage' => 50, 'grade_point' => 4]]])
            ->assertJsonValidationErrors('bands.1.grade');
    }

    // ------------------------------------------------------- authorization ---

    public function test_finalize_locks_grades_and_only_super_admin_reopens(): void
    {
        Notification::fake();
        $this->seedScores();
        $base = "/api/sections/{$this->section->id}/grades";
        $uniAdmin = User::factory()->create(['role_id' => RoleModel::factory()->withSlug(Role::UniversityAdmin->value)->create()->id]);

        // Nothing approved yet.
        $this->actingAs($this->admin)->postJson("{$base}/finalize")->assertStatus(409);

        $this->actingAs($this->lecturer->user)->postJson($base);
        $this->actingAs($this->lecturer->user)->postJson("{$base}/submit");
        $this->actingAs($this->admin)->postJson("{$base}/approve");

        $this->actingAs($this->lecturer->user)->postJson("{$base}/finalize")->assertForbidden();
        $this->actingAs($uniAdmin)->postJson("{$base}/finalize")->assertOk()
            ->assertJsonPath('saved', 2)->assertJsonPath('counts.finalized', 2)->assertJsonPath('counts.approved', 0);
        $this->assertTrue(AuditLog::query()->where('action', 'grades.finalized')->exists());

        // Locked: return to draft has nothing to touch, recompute skips them, GPA and the student view are unchanged.
        $this->actingAs($this->admin)->postJson("{$base}/return")->assertStatus(409);
        $this->actingAs($this->lecturer->user)->postJson($base)->assertOk()->assertJsonPath('saved', 0);
        $this->assertEquals(4.0, GpaRecord::query()->where('student_id', $this->alice->id)->where('cumulative', true)->value('gpa_value'));
        $this->actingAs($this->alice->user)->getJson("/api/students/{$this->alice->id}/grades")->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.letter_grade', 'A');

        // Reopen: Super Admin only, reason required, audited.
        $this->actingAs($uniAdmin)->postJson("{$base}/reopen", ['reason' => 'Typo'])->assertForbidden();
        $this->actingAs($this->admin)->postJson("{$base}/reopen")->assertJsonValidationErrors('reason');
        $this->actingAs($this->admin)->postJson("{$base}/reopen", ['reason' => 'Marking error on the final exam'])->assertOk()
            ->assertJsonPath('counts.approved', 2)->assertJsonPath('counts.finalized', 0);
        $this->assertSame('Marking error on the final exam', AuditLog::query()->where('action', 'grades.reopened')->value('description'));
        $this->actingAs($this->admin)->postJson("{$base}/reopen", ['reason' => 'Again'])->assertStatus(409);

        // Web actions.
        $this->actingAs($this->admin)->post("/grades/sections/{$this->section->id}/finalize")->assertRedirect()->assertSessionHas('success');
        $this->actingAs($this->admin)->get("/grades/sections/{$this->section->id}")
            ->assertInertia(fn (Assert $page) => $page->where('canReopen', true)->where('sheet.counts.finalized', 2));
        $this->actingAs($uniAdmin)->get('/grades?status=all')
            ->assertInertia(fn (Assert $page) => $page->where('sections.data.0.counts.finalized', 2));
        $this->actingAs($uniAdmin)->post("/grades/sections/{$this->section->id}/reopen", ['reason' => 'x'])->assertForbidden();
        $this->actingAs($this->admin)->post("/grades/sections/{$this->section->id}/reopen", ['reason' => 'Correction'])->assertRedirect()->assertSessionHas('success');
    }

    public function test_role_matrix(): void
    {
        $base = "/api/sections/{$this->section->id}/grades";
        $departmentAdmin = $this->departmentAdminFor($this->departmentOfSection($this->section));
        $this->placeInDepartment($this->bob, $departmentAdmin->department_id);
        $otherDepartmentAdmin = $this->departmentAdminFor(Department::factory()->create());
        $outsider = Lecturer::factory()->create();

        $this->actingAs($departmentAdmin)->getJson($base)->assertOk();
        $this->actingAs($otherDepartmentAdmin)->getJson($base)->assertForbidden();
        $this->actingAs($otherDepartmentAdmin)->getJson("/api/students/{$this->bob->id}/gpa")->assertForbidden();
        $this->actingAs($departmentAdmin)->postJson($base)->assertForbidden();
        $this->actingAs($outsider->user)->getJson($base)->assertForbidden();
        $this->actingAs($outsider->user)->postJson($base)->assertForbidden();
        $this->actingAs($this->alice->user)->getJson($base)->assertForbidden();
        $this->actingAs($this->alice->user)->postJson($base)->assertForbidden();

        // Inactive lecturer loses access.
        $this->lecturer->update(['is_active' => false]);
        $this->actingAs($this->lecturer->user)->getJson($base)->assertForbidden();

        // Students read only their own grades.
        $this->actingAs($this->alice->user)->getJson("/api/students/{$this->alice->id}/grades")->assertOk();
        $this->actingAs($this->alice->user)->getJson("/api/students/{$this->bob->id}/grades")->assertForbidden();
        $this->actingAs($departmentAdmin)->getJson("/api/students/{$this->bob->id}/gpa")->assertOk();
    }

    public function test_students_see_only_approved_grades(): void
    {
        $this->seedScores();
        $base = "/api/sections/{$this->section->id}/grades";
        $this->actingAs($this->lecturer->user)->postJson($base);
        $this->actingAs($this->lecturer->user)->postJson("{$base}/submit");

        $this->actingAs($this->alice->user)->getJson("/api/students/{$this->alice->id}/grades")->assertOk()->assertJsonCount(0, 'data');

        $this->actingAs($this->admin)->postJson("{$base}/approve");
        $this->actingAs($this->alice->user)->getJson("/api/students/{$this->alice->id}/grades")->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.letter_grade', 'A')->assertJsonPath('gpa.cumulative.gpa', 4);
    }

    // ----------------------------------------------------------------- web ---

    public function test_web_pages_and_actions(): void
    {
        $this->seedScores();

        $this->actingAs($this->lecturer->user)->get("/grades/sections/{$this->section->id}")->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Grades/Section')->where('canGrade', true)->where('canApprove', false)->has('sheet.data', 2)->has('scale', 7));
        $this->actingAs($this->lecturer->user)->post("/grades/sections/{$this->section->id}")->assertRedirect()->assertSessionHas('success');
        $this->actingAs($this->lecturer->user)->post("/grades/sections/{$this->section->id}/submit")->assertRedirect()->assertSessionHas('success');
        $this->actingAs($this->lecturer->user)->post("/grades/sections/{$this->section->id}/approve")->assertForbidden();

        $this->actingAs($this->admin)->get('/grades')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Grades/Index')->where('status', 'submitted')->has('sections.data', 1)->where('sections.data.0.counts.submitted', 2));
        $this->actingAs($this->admin)->post("/grades/sections/{$this->section->id}/approve")->assertRedirect()->assertSessionHas('success');
        $this->actingAs($this->admin)->get('/grades')->assertInertia(fn (Assert $page) => $page->has('sections.data', 0));
        $this->actingAs($this->admin)->get('/grades?status=all')->assertInertia(fn (Assert $page) => $page->has('sections.data', 1));

        $this->actingAs($this->alice->user)->get('/my-grades')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Grades/Mine')->has('grades', 1)->where('gpa.cumulative.gpa', 4));
        $this->actingAs($this->alice->user)->get('/grades')->assertForbidden();
        $this->actingAs($this->lecturer->user)->get('/grading-scale')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Grades/Scale')->where('canEdit', false));
        $this->actingAs($this->admin)->put("/courses/{$this->section->offering->course_id}/grading-config", [
            'attendance_weight' => 0, 'assignment_weight' => 30, 'midterm_weight' => 30, 'final_weight' => 40, 'practical_weight' => 0,
        ])->assertRedirect()->assertSessionHas('success');
        $this->actingAs($this->admin)->get("/courses/{$this->section->offering->course_id}/edit")->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('gradingConfig.assignment_weight', 30)->where('gradingConfig.is_default', false));
    }

    // ------------------------------------------------------------- helpers ---

    /** Midterm 80 / 40, final 90 / 50, one assignment 18 / 20 for Alice only. */
    private function seedScores(): void
    {
        $midterm = Exam::factory()->create(['section_id' => $this->section->id, 'exam_type' => 'midterm', 'weight' => 30]);
        $final = Exam::factory()->create(['section_id' => $this->section->id, 'exam_type' => 'final', 'title' => 'Final', 'weight' => 40]);
        foreach ([[$midterm, 80, 40], [$final, 90, 50]] as [$exam, $a, $b]) {
            ExamResult::query()->create(['exam_id' => $exam->id, 'enrollment_id' => $this->aliceEnrollment->id, 'score' => $a]);
            ExamResult::query()->create(['exam_id' => $exam->id, 'enrollment_id' => $this->bobEnrollment->id, 'score' => $b]);
        }

        $assignment = Assignment::factory()->create(['section_id' => $this->section->id, 'max_score' => 20]);
        AssignmentSubmission::query()->create(['assignment_id' => $assignment->id, 'enrollment_id' => $this->aliceEnrollment->id, 'status' => 'graded', 'score' => 18]);
    }

    private function approvedGrade(Student $student, Semester $semester, Course $course, string $letter, float $point): void
    {
        $section = $this->newSection($semester, $course);
        $enrollment = Enrollment::query()->create([
            'student_id' => $student->id, 'section_id' => $section->id, 'academic_year_id' => $semester->academic_year_id,
            'semester_id' => $semester->id, 'status' => Enrollment::STATUS_COMPLETED, 'enrolled_at' => now(),
        ]);
        Grade::query()->create(['enrollment_id' => $enrollment->id, 'letter_grade' => $letter, 'grade_point' => $point, 'status' => Grade::STATUS_APPROVED, 'approved_at' => now()]);
    }

    private function newSection(Semester $semester, Course $course): Section
    {
        $offering = CourseOffering::factory()->create(['course_id' => $course->id, 'semester_id' => $semester->id, 'status' => 'open']);

        return Section::factory()->create(['course_offering_id' => $offering->id, 'status' => 'open', 'capacity' => 30]);
    }
}
