<?php

namespace Tests\Feature\Analytics;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Enrollment;
use App\Models\GpaRecord;
use App\Models\Grade;
use App\Models\Internship;
use App\Models\InternshipCompany;
use App\Models\Lecturer;
use App\Models\Program;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Services\GradingService;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Department analytics and trends (`docs/47_Department-Analytics-and-Trends-Report.md`).
 *
 * Fixture, current semester: Engineering students a, b (program BSCE) and Law
 * student c (LLB). a takes EN101 and LW101, b EN101, c LW101 and EN101 — so the
 * department's students and the department's courses are different sets.
 */
class DepartmentAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private Department $eng;

    private Department $law;

    private Semester $semester;

    private Semester $previous;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Carbon::setTestNow('2026-03-10 09:00:00');
        app(GradingService::class)->saveScale(GradingService::DEFAULT_BANDS);
        $this->seed(DocumentTypeSeeder::class);

        $this->admin = User::factory()->superAdmin()->create();
        $this->eng = Department::factory()->create(['code' => 'ENG']);
        $this->law = Department::factory()->create(['code' => 'LAW']);
        $this->semester = Semester::factory()->create(['status' => 'open', 'start_date' => '2026-02-01', 'end_date' => '2026-06-30']);
        $this->previous = Semester::factory()->create(['status' => 'completed', 'start_date' => '2025-09-01', 'end_date' => '2026-01-15']);
        $this->fixture();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_department_figures_count_its_students_and_its_courses(): void
    {
        $as = $this->actingAs($this->departmentAdminFor($this->eng));

        $overview = $as->getJson('/api/analytics/overview')->assertOk()
            ->assertJsonPath('department.code', 'ENG')->json('data');
        $this->assertSame(2, $overview['students_active']);     // a, b
        $this->assertSame(1, $overview['lecturers_active']);
        $this->assertSame(1, $overview['sections']);            // EN101's section
        $this->assertSame(3, $overview['enrollments']);         // a ×2, b ×1 (their students, any course)
        $this->assertSame(2, $overview['students_enrolled']);
        $this->assertEquals(66.7, $overview['attendance_rate']); // EN101: present, late of 3 counted
        $this->assertSame(3, $overview['grades_approved']);     // EN101: a, b, c
        $this->assertEquals(66.7, $overview['pass_rate']);
        $this->assertEquals(1.5, $overview['average_gpa']);     // a 3.0, b 0.0

        $enrollment = $as->getJson('/api/analytics/enrollment')->assertOk()->json('data');
        $this->assertSame([['code' => 'BSCE', 'enrollments' => 3, 'students' => 2]],
            array_map(fn ($r) => ['code' => $r['code'], 'enrollments' => $r['enrollments'], 'students' => $r['students']], $enrollment['by_program']));
        $this->assertSame([['status' => 'confirmed', 'total' => 3]], $enrollment['by_status']);

        $academic = $as->getJson('/api/analytics/academic')->assertOk()->json('data');
        $this->assertSame(['EN101'], array_column($academic['courses'], 'code'));
        $this->assertSame([['EN101', 66.7]], array_map(fn ($r) => [$r['code'], $r['rate']], $academic['attendance_by_course']));
        $this->assertSame(['A' => 1, 'B' => 1, 'F' => 1], collect($academic['grade_distribution'])->where('total', '>', 0)->pluck('total', 'grade')->all());
        $this->assertSame([1, 0, 0, 1], array_column($academic['gpa_distribution'], 'total'));

        $administrative = $as->getJson('/api/analytics/administrative')->assertOk()->json('data');
        $this->assertSame(1, collect($administrative['documents'])->firstWhere('status', 'pending')['total']);
        $this->assertSame(1, collect($administrative['internships'])->firstWhere('status', 'submitted')['total']);
        $this->assertNull($administrative['invoices']);
        $this->assertNull($administrative['finance']);
    }

    public function test_managers_see_the_university_or_pick_a_department(): void
    {
        $university = $this->actingAs($this->admin)->getJson('/api/analytics/overview')->assertOk()
            ->assertJsonPath('department', null)->json('data');
        $this->assertSame(5, $university['enrollments']);
        $this->assertSame(2, $university['sections']);
        $this->assertEquals(60, $university['attendance_rate']);  // 3 attended of 5 counted
        $this->assertEquals(60, $university['pass_rate']);        // 3 passed of 5 graded
        $this->assertEquals(1.67, $university['average_gpa']);

        $law = $this->actingAs($this->admin)->getJson("/api/analytics/overview?department_id={$this->law->id}")->assertOk()
            ->assertJsonPath('department.code', 'LAW')->json('data');
        $this->assertSame(2, $law['enrollments']);   // c ×2
        $this->assertEquals(50, $law['pass_rate']);  // LW101: a A, c F

        $this->actingAs($this->admin)->getJson('/api/analytics/overview?department_id=999999')->assertJsonValidationErrors('department_id');
        $this->actingAs($this->admin)->getJson('/api/analytics/administrative')->assertOk()->assertJsonCount(0, 'data.finance');
    }

    public function test_a_department_admin_cannot_look_at_another_department(): void
    {
        $as = $this->actingAs($this->departmentAdminFor($this->eng));

        foreach (['overview', 'enrollment', 'academic', 'administrative', 'trends'] as $endpoint) {
            $as->getJson("/api/analytics/{$endpoint}?department_id={$this->law->id}")->assertForbidden();
            $as->getJson("/api/analytics/{$endpoint}?department_id={$this->eng->id}")->assertOk();
        }
        $as->get("/analytics?department_id={$this->law->id}")->assertForbidden();
        $as->get("/analytics/export?table=course_results&department_id={$this->law->id}")->assertForbidden();
        $as->get("/analytics/export/pdf?department_id={$this->law->id}")->assertForbidden();
    }

    public function test_trends_cover_the_latest_semesters_oldest_first(): void
    {
        $trends = $this->actingAs($this->departmentAdminFor($this->eng))->getJson('/api/analytics/trends')->assertOk()->json('data');

        $this->assertSame([$this->previous->id, $this->semester->id], array_column($trends, 'semester_id'));
        $this->assertSame([1, 3], array_column($trends, 'enrollments'));
        $this->assertSame([null, 66.7], array_map(fn ($r) => $r['attendance_rate'] === null ? null : (float) $r['attendance_rate'], $trends));
        $this->assertSame([null, 1.5], array_map(fn ($r) => $r['average_gpa'] === null ? null : (float) $r['average_gpa'], $trends));

        $this->assertSame([1, 5], array_column($this->actingAs($this->admin)->getJson('/api/analytics/trends')->json('data'), 'enrollments'));
    }

    public function test_exports_follow_the_department_and_finance_stays_university_wide(): void
    {
        $departmentAdmin = $this->departmentAdminFor($this->eng);

        $csv = $this->actingAs($departmentAdmin)->get('/api/analytics/export?table=course_results')->assertOk();
        $this->assertStringContainsString('analytics-course-results-eng-', $csv->headers->get('content-disposition'));
        $this->assertStringContainsString('EN101', $csv->streamedContent());
        $this->assertStringNotContainsString('LW101', $csv->streamedContent());
        $this->assertSame($this->eng->id, AuditLog::query()->where('action', 'export.analytics')->latest('id')->first()->after_values['filters']['department_id']);

        $trends = $this->actingAs($departmentAdmin)->get('/api/analytics/export?table=trends')->assertOk()->streamedContent();
        $this->assertStringContainsString('Average semester GPA', $trends);

        $this->actingAs($departmentAdmin)->get('/api/analytics/export?table=finance')->assertForbidden();
        $this->actingAs($this->admin)->getJson("/api/analytics/export?table=finance&department_id={$this->eng->id}")->assertJsonValidationErrors('department_id');
        $this->actingAs($this->admin)->get('/api/analytics/export?table=finance')->assertOk();

        $pdf = $this->actingAs($departmentAdmin)->get('/analytics/export/pdf')->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->assertStringContainsString('analytics-report-eng-', $pdf->headers->get('content-disposition'));
        $this->assertSame($this->eng->id, AuditLog::query()->where('action', 'export.analytics_pdf')->latest('id')->first()->after_values['filters']['department_id']);
    }

    public function test_the_page_shows_the_department_and_offers_managers_the_filter(): void
    {
        $this->actingAs($this->departmentAdminFor($this->eng))->get('/analytics')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Analytics/Index')
                ->where('scope.department.code', 'ENG')->where('scope.locked', true)->has('departments', 0)
                ->where('overview.enrollments', 3)->where('administrative.finance', null)->has('trends', 2));

        $this->actingAs($this->admin)->get("/analytics?department_id={$this->law->id}")->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('scope.department.code', 'LAW')->where('scope.locked', false)
                ->has('departments', 2)->where('overview.enrollments', 2));
    }

    private function fixture(): void
    {
        $bsce = Program::factory()->create(['code' => 'BSCE', 'department_id' => $this->eng->id]);
        $llb = Program::factory()->create(['code' => 'LLB', 'department_id' => $this->law->id]);
        Lecturer::factory()->create(['department_id' => $this->eng->id]);
        Lecturer::factory()->create(['department_id' => $this->law->id]);
        $en = $this->section(Course::factory()->create(['code' => 'EN101', 'department_id' => $this->eng->id]), $this->semester);
        $lw = $this->section(Course::factory()->create(['code' => 'LW101', 'department_id' => $this->law->id]), $this->semester);
        $a = Student::factory()->inProgram($bsce)->create();
        $b = Student::factory()->inProgram($bsce)->create();
        $c = Student::factory()->inProgram($llb)->create();

        $aEn = $this->enroll($a, $en);
        $aLw = $this->enroll($a, $lw);
        $bEn = $this->enroll($b, $en);
        $cLw = $this->enroll($c, $lw);
        $cEn = $this->enroll($c, $en);
        // Previous semester: one Engineering enrollment, no grades or attendance.
        $this->enroll($a, $this->section(Course::factory()->create(['department_id' => $this->eng->id]), $this->previous));

        // Attendance — EN101: present, absent, late (2 of 3); LW101: absent, present (1 of 2).
        $this->held($en, [$aEn => 'present', $bEn => 'absent', $cEn => 'late']);
        $this->held($lw, [$aLw => 'absent', $cLw => 'present']);

        // Approved grades — EN101: A, F, B; LW101: A, F.
        foreach ([[$aEn, 'A', 4], [$bEn, 'F', 0], [$cEn, 'B', 3], [$aLw, 'A', 4], [$cLw, 'F', 0]] as [$enrollment, $letter, $point]) {
            Grade::query()->create(['enrollment_id' => $enrollment, 'letter_grade' => $letter, 'grade_point' => $point, 'total_score' => $point * 20 + 10, 'status' => 'approved']);
        }
        foreach ([[$a, 3.0], [$b, 0.0], [$c, 2.0]] as [$student, $gpa]) {
            GpaRecord::query()->create(['student_id' => $student->id, 'academic_year_id' => $this->semester->academic_year_id, 'semester_id' => $this->semester->id, 'gpa_value' => $gpa, 'attempted_credits' => 3, 'earned_credits' => $gpa > 0 ? 3 : 0, 'grade_points' => $gpa * 3]);
        }

        // Workload: one pending request and one submitted internship per department.
        $company = InternshipCompany::query()->create(['name' => 'Smart Axiata', 'industry' => 'Telecom', 'is_active' => true]);
        foreach ([$a, $c] as $student) {
            DocumentRequest::query()->create(['student_id' => $student->id, 'document_type_id' => DocumentType::query()->value('id'), 'status' => 'pending']);
        }
        foreach ([$b, $c] as $student) {
            Internship::query()->create(['student_id' => $student->id, 'company_id' => $company->id, 'position_title' => 'Intern', 'status' => 'submitted']);
        }
    }

    private function section(Course $course, Semester $semester): Section
    {
        $offering = CourseOffering::factory()->create(['course_id' => $course->id, 'semester_id' => $semester->id, 'status' => 'open']);

        return Section::factory()->create(['course_offering_id' => $offering->id, 'status' => 'open', 'capacity' => 30]);
    }

    private function enroll(Student $student, Section $section): int
    {
        $semester = $section->offering->semester;

        return Enrollment::query()->create([
            'student_id' => $student->id, 'section_id' => $section->id, 'academic_year_id' => $semester->academic_year_id,
            'semester_id' => $semester->id, 'status' => 'confirmed', 'enrolled_at' => now(),
        ])->id;
    }

    /**
     * @param  array<int, string>  $marks  enrollment id => status
     */
    private function held(Section $section, array $marks): void
    {
        $session = AttendanceSession::query()->create(['section_id' => $section->id, 'session_date' => '2026-02-02', 'status' => 'held']);
        foreach ($marks as $enrollmentId => $status) {
            AttendanceRecord::query()->create(['attendance_session_id' => $session->id, 'enrollment_id' => $enrollmentId, 'status' => $status]);
        }
    }
}
