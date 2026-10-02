<?php

namespace Tests\Feature\Exports;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\Role as RoleModel;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\InvoiceService;
use App\Support\CsvExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * CSV exports (`docs/31_CSV-Exports-Report.md`): same filters and access as
 * the lists, every export audited, formula cells neutralized.
 */
class CsvExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $uniAdmin;

    private User $facultyAdmin;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->admin = User::factory()->superAdmin()->create();
        $this->uniAdmin = User::factory()->create(['role_id' => RoleModel::factory()->withSlug(Role::UniversityAdmin->value)->create()->id]);
        $this->facultyAdmin = User::factory()->create(['role_id' => RoleModel::factory()->withSlug(Role::FacultyAdmin->value)->create()->id]);
        $this->student = Student::factory()->create(['first_name' => 'Sokha', 'last_name' => 'Chan']);
    }

    public function test_cells_are_neutralized_and_typed(): void
    {
        $this->assertSame("'=HYPERLINK(\"x\")", CsvExport::cell('=HYPERLINK("x")'));
        $this->assertSame("'+1", CsvExport::cell('+1'));
        $this->assertSame("'@SUM(A1)", CsvExport::cell('@SUM(A1)'));
        $this->assertSame(-5, CsvExport::cell(-5));
        $this->assertSame(12.5, CsvExport::cell(12.5));
        $this->assertSame('', CsvExport::cell(null));
        $this->assertSame('yes', CsvExport::cell(true));
        $this->assertSame('សុខា', CsvExport::cell('សុខា'));
    }

    public function test_invoices_export_follows_filters_access_and_is_audited(): void
    {
        $invoices = app(InvoiceService::class);
        $invoices->create(['student_id' => $this->student->id, 'title' => '=cmd|calc', 'due_date' => now()->addMonth()->toDateString(), 'items' => [['description' => 'Tuition', 'quantity' => 1, 'unit_price' => 500]]]);
        $paid = $invoices->create(['student_id' => $this->student->id, 'title' => 'Library fee', 'due_date' => now()->addMonth()->toDateString(), 'items' => [['description' => 'Fee', 'quantity' => 1, 'unit_price' => 10]]]);
        $paid->update(['status' => 'cancelled']);

        $response = $this->actingAs($this->uniAdmin)->get('/api/invoices/export')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename=invoices-', $response->headers->get('content-disposition'));
        $rows = $this->rows($response);
        $this->assertSame('Invoice', $rows[0][0]);
        $this->assertCount(3, $rows);
        // Formula text is neutralized; numbers stay numbers.
        $this->assertContains("'=cmd|calc", array_column($rows, 5));
        $this->assertContains('500', array_column($rows, 9));

        $rows = $this->rows($this->actingAs($this->admin)->get('/api/invoices/export?filters[status]=cancelled')->assertOk());
        $this->assertCount(2, $rows);
        $this->assertSame('Library fee', $rows[1][5]);

        $log = AuditLog::query()->where('action', 'export.invoices')->latest('id')->first();
        $this->assertEquals(['filters' => ['status' => 'cancelled'], 'rows' => 1], $log->after_values);
        $this->assertSame($this->admin->id, $log->actor_id);

        $this->actingAs($this->facultyAdmin)->get('/api/invoices/export')->assertForbidden();
        $this->actingAs($this->student->user)->get('/api/invoices/export')->assertForbidden();
        $this->actingAs($this->admin)->getJson('/api/invoices/export?filters[status]=bogus')->assertJsonValidationErrors('filters.status');

        // Web route: same file through the session.
        $this->assertCount(3, $this->rows($this->actingAs($this->uniAdmin)->get('/invoices/export')->assertOk()));
        $this->actingAs($this->facultyAdmin)->get('/invoices/export')->assertForbidden();
    }

    public function test_enrollments_export_for_staff_with_filters(): void
    {
        $confirmed = Enrollment::factory()->create(['student_id' => $this->student->id]);
        Enrollment::factory()->completed()->create();

        $rows = $this->rows($this->actingAs($this->facultyAdmin)->get('/api/enrollments/export')->assertOk());
        $this->assertSame(['Student ID', 'Student', 'Course', 'Course name', 'Section', 'Semester', 'Credits', 'Status', 'Enrolled at', 'Dropped at'], $rows[0]);
        $this->assertCount(3, $rows);

        $rows = $this->rows($this->actingAs($this->admin)->get('/api/enrollments/export?filters[status]=confirmed&search=Sokha')->assertOk());
        $this->assertCount(2, $rows);
        $this->assertSame([$this->student->student_number, 'Sokha Chan'], array_slice($rows[1], 0, 2));
        $this->assertSame($confirmed->section->code, $rows[1][4]);
        $this->assertTrue(AuditLog::query()->where('action', 'export.enrollments')->exists());

        $this->actingAs($this->student->user)->get('/api/enrollments/export')->assertForbidden();
        $this->assertCount(3, $this->rows($this->actingAs($this->facultyAdmin)->get('/enrollments/export')->assertOk()));
    }

    public function test_audit_log_export_is_super_admin_only_and_audits_itself(): void
    {
        app(AuditLogger::class)->record('invoice.created', null, [], ['total' => 10], 'Seeded entry', $this->admin);

        $rows = $this->rows($this->actingAs($this->admin)->get('/api/audit-logs/export?filters[area]=invoice')->assertOk());
        $this->assertCount(2, $rows);
        $this->assertSame('invoice.created', $rows[1][1]);
        $this->assertSame('{"total":10}', $rows[1][9]);

        // The export left its own trace, which a later export includes.
        $this->assertSame(1, AuditLog::query()->where('action', 'export.audit_logs')->count());
        $rows = $this->rows($this->actingAs($this->admin)->get('/audit-logs/export?filters[area]=export')->assertOk());
        $this->assertSame('export.audit_logs', $rows[1][1]);

        $this->actingAs($this->uniAdmin)->get('/api/audit-logs/export')->assertForbidden();
        $this->actingAs($this->uniAdmin)->get('/audit-logs/export')->assertForbidden();
    }

    public function test_analytics_tables_export(): void
    {
        // No semester yet: semester tables refuse, point-in-time ones work.
        $this->actingAs($this->admin)->getJson('/api/analytics/export?table=grade_distribution')->assertStatus(409);
        $rows = $this->rows($this->actingAs($this->admin)->get('/api/analytics/export?table=workload')->assertOk());
        $this->assertSame(['Area', 'Status', 'Total'], $rows[0]);
        $this->assertSame(['Document requests', 'pending', '0'], $rows[1]);

        $semester = Semester::factory()->create();
        Enrollment::factory()->create(['student_id' => $this->student->id]);

        foreach (['enrollment_by_program', 'attendance_by_course', 'grade_distribution', 'gpa_distribution', 'course_results', 'finance'] as $table) {
            $this->actingAs($this->uniAdmin)->get("/api/analytics/export?table={$table}&semester_id={$semester->id}")->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        }
        $rows = $this->rows($this->actingAs($this->uniAdmin)->get("/analytics/export?table=gpa_distribution&semester_id={$semester->id}")->assertOk());
        $this->assertSame(['Semester GPA band', 'Students'], $rows[0]);
        $this->assertCount(5, $rows);

        $this->actingAs($this->admin)->getJson('/api/analytics/export?table=secrets')->assertJsonValidationErrors('table');
        $this->actingAs($this->facultyAdmin)->get('/api/analytics/export?table=finance')->assertForbidden();
        $this->assertSame(8, AuditLog::query()->where('action', 'export.analytics')->count());
    }

    /**
     * @return list<list<string>>
     */
    private function rows(TestResponse $response): array
    {
        $content = $response->streamedContent();
        $this->assertStringStartsWith("\u{FEFF}", $content);
        $lines = preg_split('/\r?\n/', trim(substr($content, 3)));

        return array_map(fn ($line) => str_getcsv($line, escape: ''), $lines);
    }
}
