<?php

namespace Tests\Feature\Finance;

use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Program;
use App\Models\Role as RoleModel;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class TuitionInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $univAdmin;

    private User $deptAdmin;

    private Student $studentA;

    private Student $studentB;

    private Semester $semester;

    private Program $program;

    private Course $course1;

    private Course $course2;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-05 10:00:00');

        $department = Department::factory()->create();
        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->univAdmin = $this->userWithRole('university-admin');
        $this->deptAdmin = $this->departmentAdminFor($department);

        $this->semester = Semester::factory()->create([
            'status' => 'open',
            'start_date' => '2026-11-01',
            'end_date' => '2027-02-28',
        ]);

        $this->program = Program::factory()->create([
            'department_id' => $department->id,
            'tuition_per_credit' => 60.00,
        ]);

        $this->studentA = Student::factory()->create();
        $this->studentB = Student::factory()->create();

        StudentProgram::query()->create([
            'student_id' => $this->studentA->id,
            'program_id' => $this->program->id,
            'status' => StudentProgram::STATUS_ACTIVE,
            'started_on' => '2026-09-01',
        ]);

        StudentProgram::query()->create([
            'student_id' => $this->studentB->id,
            'program_id' => $this->program->id,
            'status' => StudentProgram::STATUS_ACTIVE,
            'started_on' => '2026-09-01',
        ]);

        $this->course1 = Course::factory()->create([
            'department_id' => $department->id,
            'code' => 'CS101',
            'name' => 'Programming Basics',
            'credits' => 3.0,
        ]);

        $this->course2 = Course::factory()->create([
            'department_id' => $department->id,
            'code' => 'CS102',
            'name' => 'Data Structures',
            'credits' => 4.0,
        ]);

        $offering1 = CourseOffering::factory()->create([
            'course_id' => $this->course1->id,
            'semester_id' => $this->semester->id,
        ]);

        $offering2 = CourseOffering::factory()->create([
            'course_id' => $this->course2->id,
            'semester_id' => $this->semester->id,
        ]);

        $section1 = Section::factory()->create(['course_offering_id' => $offering1->id]);
        $section2 = Section::factory()->create(['course_offering_id' => $offering2->id]);

        // Student A enrolled in Course 1 (3 credits) and Course 2 (4 credits) => 7 credits
        Enrollment::query()->create([
            'student_id' => $this->studentA->id,
            'section_id' => $section1->id,
            'academic_year_id' => $this->semester->academic_year_id,
            'semester_id' => $this->semester->id,
            'status' => Enrollment::STATUS_CONFIRMED,
            'enrolled_at' => now(),
        ]);
        Enrollment::query()->create([
            'student_id' => $this->studentA->id,
            'section_id' => $section2->id,
            'academic_year_id' => $this->semester->academic_year_id,
            'semester_id' => $this->semester->id,
            'status' => Enrollment::STATUS_CONFIRMED,
            'enrolled_at' => now(),
        ]);

        // Student B enrolled in Course 1 (3 credits) => 3 credits
        Enrollment::query()->create([
            'student_id' => $this->studentB->id,
            'section_id' => $section1->id,
            'academic_year_id' => $this->semester->academic_year_id,
            'semester_id' => $this->semester->id,
            'status' => Enrollment::STATUS_CONFIRMED,
            'enrolled_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_tuition_generation_creates_itemized_invoices_for_enrolled_students(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/invoices/generate-tuition', [
                'semester_id' => $this->semester->id,
                'due_date' => '2026-11-20',
            ])
            ->assertOk()
            ->json('data');

        $this->assertFalse($response['dry_run']);
        $this->assertSame(2, $response['total_students']);
        $this->assertSame(2, $response['generated_count']);
        $this->assertSame(0, $response['skipped_count']);
        $this->assertEquals(10.0, $response['total_credits']); // 7 + 3
        $this->assertEquals(600.0, $response['total_amount']); // (7 * 60) + (3 * 60) = 420 + 180 = 600

        // Verify Student A invoice
        $invoiceA = Invoice::query()->where('student_id', $this->studentA->id)->where('semester_id', $this->semester->id)->first();
        $this->assertNotNull($invoiceA);
        $this->assertSame(Invoice::STATUS_PENDING, $invoiceA->status);
        $this->assertSame('420.00', (string) $invoiceA->total);
        $this->assertSame('2026-11-20', $invoiceA->due_date->toDateString());
        $this->assertCount(2, $invoiceA->items);
        $this->assertSame('tuition', $invoiceA->items[0]->fee_category);
        $this->assertEquals(3.0, (float) $invoiceA->items[0]->quantity);
        $this->assertEquals(60.0, (float) $invoiceA->items[0]->unit_price);
        $this->assertEquals(180.0, (float) $invoiceA->items[0]->amount);

        // Verify Student B invoice
        $invoiceB = Invoice::query()->where('student_id', $this->studentB->id)->where('semester_id', $this->semester->id)->first();
        $this->assertNotNull($invoiceB);
        $this->assertSame(Invoice::STATUS_PENDING, $invoiceB->status);
        $this->assertSame('180.00', (string) $invoiceB->total);
        $this->assertCount(1, $invoiceB->items);
    }

    public function test_tuition_generation_dry_run_does_not_persist_invoices(): void
    {
        $response = $this->actingAs($this->univAdmin)
            ->postJson('/api/invoices/generate-tuition', [
                'semester_id' => $this->semester->id,
                'dry_run' => true,
            ])
            ->assertOk()
            ->json('data');

        $this->assertTrue($response['dry_run']);
        $this->assertSame(2, $response['generated_count']);
        $this->assertEquals(600.0, $response['total_amount']);

        // Verify database is untouched
        $this->assertSame(0, Invoice::query()->where('semester_id', $this->semester->id)->count());
    }

    public function test_tuition_generation_skips_students_already_invoiced_for_the_semester(): void
    {
        // First run creates 2 invoices
        $this->actingAs($this->superAdmin)
            ->postJson('/api/invoices/generate-tuition', [
                'semester_id' => $this->semester->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.generated_count', 2);

        $this->assertSame(2, Invoice::query()->where('semester_id', $this->semester->id)->count());

        // Second run detects existing invoices and skips them
        $res = $this->actingAs($this->superAdmin)
            ->postJson('/api/invoices/generate-tuition', [
                'semester_id' => $this->semester->id,
            ])
            ->assertOk()
            ->json('data');

        $this->assertSame(0, $res['generated_count']);
        $this->assertSame(2, $res['skipped_count']);
        $this->assertSame(2, Invoice::query()->where('semester_id', $this->semester->id)->count());
    }

    public function test_rate_override_takes_precedence_over_program_rate(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('/api/invoices/generate-tuition', [
                'semester_id' => $this->semester->id,
                'rate_per_credit' => 75.00,
            ])
            ->assertOk()
            ->assertJsonPath('data.total_amount', 750); // 10 credits * 75 = 750

        $invoiceB = Invoice::query()->where('student_id', $this->studentB->id)->first();
        $this->assertSame('225.00', (string) $invoiceB->total); // 3 * 75 = 225
        $this->assertEquals(75.00, (float) $invoiceB->items->first()->unit_price);
    }

    public function test_authorization_checks_for_tuition_generation(): void
    {
        $payload = ['semester_id' => $this->semester->id];

        // Dept admin cannot generate tuition invoices (403)
        $this->actingAs($this->deptAdmin)
            ->postJson('/api/invoices/generate-tuition', $payload)
            ->assertForbidden();

        // Student cannot generate tuition invoices (403)
        $this->actingAs($this->studentA->user)
            ->postJson('/api/invoices/generate-tuition', $payload)
            ->assertForbidden();

        // Univ admin can generate
        $this->actingAs($this->univAdmin)
            ->postJson('/api/invoices/generate-tuition', $payload)
            ->assertOk();
    }

    public function test_artisan_command_generates_tuition_invoices(): void
    {
        $exitCode = Artisan::call('tuition:generate', [
            'semester' => $this->semester->id,
            '--rate' => 50,
            '--due-date' => '2026-11-30',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(2, Invoice::query()->where('semester_id', $this->semester->id)->count());

        $invoiceA = Invoice::query()->where('student_id', $this->studentA->id)->first();
        $this->assertSame('350.00', (string) $invoiceA->total); // 7 * 50 = 350
    }

    public function test_web_route_generates_tuition_invoices_and_redirects(): void
    {
        $this->actingAs($this->superAdmin)
            ->from('/invoices')
            ->post('/invoices/generate-tuition', [
                'semester_id' => $this->semester->id,
            ])
            ->assertRedirect('/invoices')
            ->assertSessionHas('success');

        $this->assertSame(2, Invoice::query()->where('semester_id', $this->semester->id)->count());
    }

    private function userWithRole(string $slug): User
    {
        $role = RoleModel::query()->firstWhere('slug', $slug) ?? RoleModel::factory()->withSlug($slug)->create();

        return User::factory()->create(['role_id' => $role->id]);
    }
}
