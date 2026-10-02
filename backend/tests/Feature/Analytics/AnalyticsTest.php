<?php

namespace Tests\Feature\Analytics;

use App\Enums\Role;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Enrollment;
use App\Models\GpaRecord;
use App\Models\Grade;
use App\Models\Lecturer;
use App\Models\Program;
use App\Models\Role as RoleModel;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Services\GradingService;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Module 9.23 — aggregates against hand-counted fixtures, semester scoping,
 * zero-data safety, currency separation, manager-only access.
 */
class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Semester $semester;

    private Semester $other;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Carbon::setTestNow('2026-03-10 09:00:00');
        app(GradingService::class)->saveScale(GradingService::DEFAULT_BANDS);

        $this->admin = User::factory()->superAdmin()->create();
        $this->semester = Semester::factory()->create(['status' => 'open', 'start_date' => '2026-02-01', 'end_date' => '2026-06-30']);
        $this->other = Semester::factory()->create(['status' => 'completed', 'start_date' => '2025-09-01', 'end_date' => '2026-01-15']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_aggregates_match_hand_counts(): void
    {
        $cs = Program::factory()->create(['code' => 'BSCS']);
        $it = Program::factory()->create(['code' => 'BSIT']);
        $math = $this->section(Course::factory()->create(['code' => 'MA101']));
        $prog = $this->section(Course::factory()->create(['code' => 'CS101']));
        $a = Student::factory()->inProgram($cs)->create();
        $b = Student::factory()->inProgram($cs)->create();
        $c = Student::factory()->inProgram($it)->create();

        // 5 enrollments this semester (BSCS 4, BSIT 1), 1 dropped (excluded), 1 in another semester (excluded).
        $ea1 = $this->enroll($a, $math);
        $this->enroll($a, $prog);
        $eb1 = $this->enroll($b, $math);
        $this->enroll($b, $prog);
        $this->enroll($c, $math);
        $this->enroll(Student::factory()->inProgram($it)->create(), $prog, 'dropped');
        $this->enroll($c, $this->section(Course::factory()->create(), $this->other));

        // Attendance: MA101 present, late, absent, excused → 2/3 = 66.7 %; CS101 present → 100 %.
        $s1 = AttendanceSession::query()->create(['section_id' => $math->id, 'session_date' => '2026-02-02', 'status' => 'held']);
        foreach (['present', 'late', 'absent'] as $i => $status) {
            AttendanceRecord::query()->create(['attendance_session_id' => $s1->id, 'enrollment_id' => [$ea1, $eb1, Enrollment::query()->where('student_id', $c->id)->where('section_id', $math->id)->value('id')][$i], 'status' => $status]);
        }
        $s2 = AttendanceSession::query()->create(['section_id' => $prog->id, 'session_date' => '2026-02-03', 'status' => 'held']);
        AttendanceRecord::query()->create(['attendance_session_id' => $s2->id, 'enrollment_id' => Enrollment::query()->where('student_id', $a->id)->where('section_id', $prog->id)->value('id'), 'status' => 'present']);
        $s3 = AttendanceSession::query()->create(['section_id' => $prog->id, 'session_date' => '2026-02-04', 'status' => 'held']);
        AttendanceRecord::query()->create(['attendance_session_id' => $s3->id, 'enrollment_id' => Enrollment::query()->where('student_id', $b->id)->where('section_id', $prog->id)->value('id'), 'status' => 'excused']);

        // Grades (MA101): A 4.0 approved, F 0 approved, B draft (excluded).
        Grade::query()->create(['enrollment_id' => $ea1, 'letter_grade' => 'A', 'grade_point' => 4, 'total_score' => 90, 'status' => 'approved']);
        Grade::query()->create(['enrollment_id' => $eb1, 'letter_grade' => 'F', 'grade_point' => 0, 'total_score' => 30, 'status' => 'approved']);
        Grade::query()->create(['enrollment_id' => Enrollment::query()->where('student_id', $c->id)->where('section_id', $math->id)->value('id'), 'letter_grade' => 'B', 'grade_point' => 3, 'status' => 'draft']);
        foreach ([[$a, 4.0], [$b, 0.0]] as [$student, $gpa]) {
            GpaRecord::query()->create(['student_id' => $student->id, 'academic_year_id' => $this->semester->academic_year_id, 'semester_id' => $this->semester->id, 'gpa_value' => $gpa, 'attempted_credits' => 3, 'earned_credits' => $gpa > 0 ? 3 : 0, 'grade_points' => $gpa * 3]);
        }

        $overview = $this->actingAs($this->admin)->getJson("/api/analytics/overview?semester_id={$this->semester->id}")->assertOk()
            ->assertJsonPath('semester.id', $this->semester->id)->json('data');
        $this->assertSame(5, $overview['enrollments']);
        $this->assertSame(3, $overview['students_enrolled']);
        $this->assertSame(2, $overview['sections']);
        $this->assertEquals(75, $overview['attendance_rate']);     // (2 + 1) / (3 + 1)
        $this->assertSame(2, $overview['grades_approved']);
        $this->assertEquals(50, $overview['pass_rate']);
        $this->assertEquals(2, $overview['average_gpa']);

        $enrollment = $this->actingAs($this->admin)->getJson('/api/analytics/enrollment')->assertOk()->json('data'); // default = open semester
        $this->assertSame([['code' => 'BSCS', 'enrollments' => 4, 'students' => 2], ['code' => 'BSIT', 'enrollments' => 1, 'students' => 1]],
            array_map(fn ($r) => ['code' => $r['code'], 'enrollments' => $r['enrollments'], 'students' => $r['students']], $enrollment['by_program']));

        $academic = $this->actingAs($this->admin)->getJson("/api/analytics/academic?semester_id={$this->semester->id}")->assertOk()->json('data');
        $letters = collect($academic['grade_distribution'])->pluck('total', 'grade');
        $this->assertSame(['A' => 1, 'B+' => 0, 'B' => 0, 'C+' => 0, 'C' => 0, 'D' => 0, 'F' => 1], $letters->all());
        $this->assertSame([1, 0, 0, 1], array_column($academic['gpa_distribution'], 'total'));
        $this->assertSame([['MA101', 66.7], ['CS101', 100]], array_map(fn ($r) => [$r['code'], $r['rate']], $academic['attendance_by_course']));
        $this->assertSame(['MA101', 2, 50, 60], [$academic['courses'][0]['code'], $academic['courses'][0]['graded'], (int) $academic['courses'][0]['pass_rate'], (int) $academic['courses'][0]['average_total']]);

        // Another semester has its own numbers.
        $this->actingAs($this->admin)->getJson("/api/analytics/overview?semester_id={$this->other->id}")->assertJsonPath('data.enrollments', 1)->assertJsonPath('data.pass_rate', null);
        $this->actingAs($this->admin)->getJson('/api/analytics/overview?semester_id=999999')->assertJsonValidationErrors('semester_id');
    }

    public function test_finance_is_never_summed_across_currencies(): void
    {
        $student = Student::factory()->create();
        $invoices = app(InvoiceService::class);
        $usd = $invoices->create(['student_id' => $student->id, 'title' => 'Tuition', 'currency' => 'USD', 'due_date' => '2026-03-01', 'issued_date' => '2026-02-01', 'items' => [['description' => 'Tuition', 'quantity' => 1, 'unit_price' => 600]]]);
        $invoices->recordPayment($usd, ['amount' => 150, 'paid_on' => '2026-03-01', 'method' => 'cash'], $this->admin);
        $invoices->create(['student_id' => $student->id, 'title' => 'Lab', 'currency' => 'KHR', 'due_date' => '2026-04-01', 'items' => [['description' => 'Lab', 'quantity' => 1, 'unit_price' => 40000]]]);
        $cancelled = $invoices->create(['student_id' => $student->id, 'title' => 'Mistake', 'currency' => 'USD', 'due_date' => '2026-04-01', 'items' => [['description' => 'x', 'quantity' => 1, 'unit_price' => 999]]]);
        $invoices->cancel($cancelled, null);

        $data = $this->actingAs($this->admin)->getJson('/api/analytics/administrative')->assertOk()->json('data');
        $finance = collect($data['finance'])->keyBy('currency');
        $this->assertEquals(['invoiced' => 600, 'collected' => 150, 'outstanding' => 450, 'overdue' => 450, 'overdue_count' => 1, 'collection_rate' => 25],
            collect($finance['USD'])->only(['invoiced', 'collected', 'outstanding', 'overdue', 'overdue_count', 'collection_rate'])->all());
        $this->assertEquals(40000, $finance['KHR']['outstanding']);
        $this->assertSame(1, collect($data['invoices'])->firstWhere('status', 'cancelled')['total']);
        $this->assertCount(4, $data['documents']);
        $this->assertCount(8, $data['internships']);
    }

    public function test_empty_system_is_safe(): void
    {
        Semester::query()->delete();

        $this->actingAs($this->admin)->getJson('/api/analytics/overview')->assertOk()->assertJsonPath('semester', null)->assertJsonPath('data', null);
        $this->actingAs($this->admin)->getJson('/api/analytics/administrative')->assertOk()->assertJsonCount(0, 'data.finance');
        $this->actingAs($this->admin)->get('/analytics')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Analytics/Index')->where('overview', null));
    }

    public function test_managers_only(): void
    {
        $faculty = User::factory()->create(['role_id' => RoleModel::factory()->withSlug(Role::FacultyAdmin->value)->create()->id]);
        $university = User::factory()->create(['role_id' => RoleModel::factory()->withSlug(Role::UniversityAdmin->value)->create()->id]);

        foreach (['overview', 'enrollment', 'academic', 'administrative'] as $endpoint) {
            $this->actingAs($faculty)->getJson("/api/analytics/{$endpoint}")->assertForbidden();
            $this->actingAs(Student::factory()->create()->user)->getJson("/api/analytics/{$endpoint}")->assertForbidden();
            $this->actingAs(Lecturer::factory()->create()->user)->getJson("/api/analytics/{$endpoint}")->assertForbidden();
            $this->actingAs($university)->getJson("/api/analytics/{$endpoint}")->assertOk();
        }

        $this->actingAs($faculty)->get('/analytics')->assertForbidden();
        $this->actingAs($university)->get("/analytics?semester_id={$this->other->id}")->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('semesterId', $this->other->id)->has('semesters', 2)->has('administrative.documents'));
    }

    private function section(Course $course, ?Semester $semester = null): Section
    {
        $offering = CourseOffering::factory()->create(['course_id' => $course->id, 'semester_id' => ($semester ?? $this->semester)->id, 'status' => 'open']);

        return Section::factory()->create(['course_offering_id' => $offering->id, 'status' => 'open', 'capacity' => 30]);
    }

    private function enroll(Student $student, Section $section, string $status = 'confirmed'): int
    {
        $semester = $section->offering->semester;

        return Enrollment::query()->create([
            'student_id' => $student->id, 'section_id' => $section->id, 'academic_year_id' => $semester->academic_year_id,
            'semester_id' => $semester->id, 'status' => $status, 'enrolled_at' => now(),
        ])->id;
    }
}
