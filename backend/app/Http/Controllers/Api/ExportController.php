<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\University;
use App\Services\AnalyticsService;
use App\Services\AuditLogger;
use App\Services\EnrollmentService;
use App\Services\InvoiceService;
use App\Support\CsvExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV exports (`docs/31_CSV-Exports-Report.md`). Each export takes the same
 * filters as its list, is authorized like that list, is audited
 * (`export.*`), and streams every matching row (no pagination). The web
 * routes use this controller too: the response is the same file.
 */
class ExportController extends Controller
{
    /** Analytics tables that can be exported; the first group needs a semester. */
    public const ANALYTICS_TABLES = [
        'enrollment_by_program', 'attendance_by_course', 'grade_distribution', 'gpa_distribution', 'course_results',
        'finance', 'workload', 'trends',
    ];

    private const SEMESTER_TABLES = ['enrollment_by_program', 'attendance_by_course', 'grade_distribution', 'gpa_distribution', 'course_results'];

    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly EnrollmentService $enrollments,
        private readonly AnalyticsService $analytics,
        private readonly AnalyticsController $analyticsApi,
        private readonly AuditLogController $auditApi,
        private readonly AuditLogger $audit,
    ) {}

    #[OA\Get(
        path: '/invoices/export',
        summary: 'Export invoices as CSV',
        description: 'Same filters as `GET /invoices`; every matching invoice, newest first. Audited as `export.invoices`.',
        operationId: 'exportInvoices',
        tags: ['Finance'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'filters[status]', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'partial', 'paid', 'overdue', 'cancelled'])),
            new OA\QueryParameter(name: 'filters[student_id]', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'search', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'CSV file (UTF-8 with BOM).', content: new OA\MediaType(mediaType: 'text/csv', schema: new OA\Schema(type: 'string'))),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid filter.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function invoices(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Invoice::class);
        $filters = InvoiceController::filters($request);
        $query = $this->invoices->query($filters);
        $this->audited('invoices', $filters, $query->count());

        return CsvExport::download(CsvExport::filename('invoices'),
            ['Invoice', 'Issued', 'Due', 'Student ID', 'Student', 'Title', 'Currency', 'Subtotal', 'Discount', 'Total', 'Paid', 'Balance', 'Status'],
            $query->lazy(500)->map(fn (Invoice $invoice) => [
                $invoice->invoice_number, $invoice->issued_date->toDateString(), $invoice->due_date->toDateString(),
                $invoice->student?->student_number, $invoice->student?->fullName(), $invoice->title, $invoice->currency,
                (float) $invoice->subtotal, (float) $invoice->discount, (float) $invoice->total, (float) $invoice->amount_paid,
                $invoice->balanceCents() / 100, $invoice->status,
            ]));
    }

    #[OA\Get(
        path: '/enrollments/export',
        summary: 'Export enrollments as CSV',
        description: 'Same filters as `GET /enrollments`; every matching enrollment, newest first. Staff only. Audited as `export.enrollments`.',
        operationId: 'exportEnrollments',
        tags: ['Academics'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'search', required: false, schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'filters[student_id]', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'filters[section_id]', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'filters[semester_id]', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'filters[status]', required: false, schema: new OA\Schema(type: 'string', enum: Enrollment::STATUSES)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'CSV file (UTF-8 with BOM).', content: new OA\MediaType(mediaType: 'text/csv', schema: new OA\Schema(type: 'string'))),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not staff (Super Admin, University Admin, Department Admin).', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function enrollments(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Enrollment::class);
        $filters = EnrollmentController::filters($request);
        $query = $this->enrollments->query($filters, $request->user());
        $this->audited('enrollments', $filters, $query->count());

        return CsvExport::download(CsvExport::filename('enrollments'),
            ['Student ID', 'Student', 'Course', 'Course name', 'Section', 'Semester', 'Credits', 'Status', 'Enrolled at', 'Dropped at'],
            $query->lazy(500)->map(fn (Enrollment $enrollment) => [
                $enrollment->student?->student_number, $enrollment->student?->fullName(),
                $enrollment->section?->offering?->course?->code, $enrollment->section?->offering?->course?->name, $enrollment->section?->code,
                trim(($enrollment->semester?->academicYear?->code ?? '').' '.($enrollment->semester?->name ?? '')),
                $enrollment->section?->offering?->course ? (float) $enrollment->section->offering->course->credits : null,
                $enrollment->status, $enrollment->enrolled_at?->toIso8601String(), $enrollment->dropped_at?->toIso8601String(),
            ]));
    }

    #[OA\Get(
        path: '/audit-logs/export',
        summary: 'Export the audit trail as CSV',
        description: 'Same filters as `GET /audit-logs`; every matching entry, newest first, with before / after values as JSON. Super Admin only. The export itself is audited (`export.audit_logs`).',
        operationId: 'exportAuditLogs',
        tags: ['Audit'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'search', required: false, schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'filters[area]', required: false, schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'filters[action]', required: false, schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'filters[actor_id]', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'from', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\QueryParameter(name: 'to', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'CSV file (UTF-8 with BOM).', content: new OA\MediaType(mediaType: 'text/csv', schema: new OA\Schema(type: 'string'))),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid filter.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function auditLogs(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', AuditLog::class);
        $query = $this->auditApi->searchQuery($request);
        // Counted before this export's own entry is written.
        $this->audited('audit_logs', $request->only('search', 'filters', 'from', 'to'), $query->count());

        return CsvExport::download(CsvExport::filename('audit-log'),
            ['Time', 'Action', 'Actor', 'Actor email', 'Target', 'Target ID', 'Description', 'IP address', 'Before', 'After'],
            $query->lazy(500)->map(fn (AuditLog $log) => [
                $log->created_at->toIso8601String(), $log->action, $log->actor?->name, $log->actor?->email,
                $log->auditable_type ? class_basename($log->auditable_type) : null, $log->auditable_id,
                $log->description, $log->ip_address,
                $log->before_values ? json_encode($log->before_values, JSON_UNESCAPED_UNICODE) : null,
                $log->after_values ? json_encode($log->after_values, JSON_UNESCAPED_UNICODE) : null,
            ]));
    }

    #[OA\Get(
        path: '/analytics/export',
        summary: 'Export one analytics table as CSV',
        description: 'The same figures as the analytics endpoints. Semester tables (`enrollment_by_program`, `attendance_by_course`, `grade_distribution`, `gpa_distribution`, `course_results`) use `semester_id` or the default semester (409 when none exists); `finance` and `workload` are point in time; `trends` covers the latest six semesters. `department_id` limits the figures to one department (a Department Admin always gets their own); `finance` is university-wide only (403 for a Department Admin, 422 with `department_id`). Audited as `export.analytics`.',
        operationId: 'exportAnalytics',
        tags: ['Analytics'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'table', required: true, schema: new OA\Schema(type: 'string', enum: self::ANALYTICS_TABLES)),
            new OA\QueryParameter(name: 'semester_id', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'department_id', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'CSV file (UTF-8 with BOM).', content: new OA\MediaType(mediaType: 'text/csv', schema: new OA\Schema(type: 'string'))),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin, University Admin or Department Admin; another department; finance for a Department Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'No semester to report on.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Unknown table or semester.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function analytics(Request $request): StreamedResponse
    {
        Gate::authorize('view-analytics');
        $table = $request->validate(['table' => ['required', Rule::in(self::ANALYTICS_TABLES)]])['table'];
        $department = $this->analyticsApi->departmentScope($request);
        if ($table === 'finance' && $department !== null) {
            // Finance is reported for the whole university only (report 47).
            abort_if($request->user()->departmentScope() !== null, 403, 'Finance is not part of department analytics.');
            throw ValidationException::withMessages(['department_id' => 'Finance is reported for the whole university only.']);
        }
        $semester = in_array($table, self::SEMESTER_TABLES, true) ? $this->analyticsApi->semester($request) : null;

        if ($semester === null && in_array($table, self::SEMESTER_TABLES, true)) {
            throw new BusinessRuleException('There is no semester to report on.');
        }

        [$header, $rows] = match ($table) {
            'enrollment_by_program' => [['Program', 'Program name', 'Enrollments', 'Students'],
                array_map(fn ($r) => [$r['code'], $r['name'], $r['enrollments'], $r['students']], $this->analytics->enrollment($semester, $department)['by_program'])],
            'attendance_by_course' => [['Course', 'Course name', 'Attendance rate (%)'],
                array_map(fn ($r) => [$r['code'], $r['name'], $r['rate']], $this->analytics->academic($semester, $department)['attendance_by_course'])],
            'grade_distribution' => [['Grade', 'Students', 'Pass'],
                array_map(fn ($r) => [$r['grade'], $r['total'], $r['is_pass']], $this->analytics->academic($semester, $department)['grade_distribution'])],
            'gpa_distribution' => [['Semester GPA band', 'Students'],
                array_map(fn ($r) => [$r['band'], $r['total']], $this->analytics->academic($semester, $department)['gpa_distribution'])],
            'course_results' => [['Course', 'Course name', 'Graded', 'Pass rate (%)', 'Average total', 'Average points'],
                array_map(fn ($r) => [$r['code'], $r['name'], $r['graded'], $r['pass_rate'], $r['average_total'], $r['average_point']], $this->analytics->academic($semester, $department)['courses'])],
            'finance' => [['Currency', 'Invoiced', 'Collected', 'Outstanding', 'Overdue', 'Overdue invoices', 'Collection rate (%)'],
                array_map(fn ($r) => [$r['currency'], $r['invoiced'], $r['collected'], $r['outstanding'], $r['overdue'], $r['overdue_count'], $r['collection_rate']], $this->analytics->administrative()['finance'])],
            'workload' => [['Area', 'Status', 'Total'], $this->workloadRows($department)],
            'trends' => [['Semester', 'Status', 'Enrollments', 'Students enrolled', 'Attendance rate (%)', 'Pass rate (%)', 'Average semester GPA'],
                array_map(fn ($r) => [$r['semester'], $r['status'], $r['enrollments'], $r['students_enrolled'], $r['attendance_rate'], $r['pass_rate'], $r['average_gpa']], $this->analytics->trends($department))],
        };

        $this->audited('analytics', ['table' => $table, 'semester_id' => $semester?->id, 'department_id' => $department], count($rows));
        $suffix = ($department ? '-'.str($this->analyticsApi->departmentSummary($department)['code'] ?? 'department')->slug() : '')
            .($semester ? '-'.str($semester->academicYear?->code.' '.$semester->name)->slug() : '');

        return CsvExport::download(CsvExport::filename('analytics-'.str_replace('_', '-', $table).$suffix), $header, $rows);
    }

    #[OA\Get(
        path: '/analytics/export/pdf',
        summary: 'Export executive analytics report as PDF',
        description: 'Comprehensive institutional analytics report for a semester as a PDF document. Audited as `export.analytics_pdf`.',
        operationId: 'exportAnalyticsPdf',
        tags: ['Analytics'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'semester_id', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'department_id', required: false, description: 'A department report (its students\' workload instead of finance). A Department Admin always gets their own.', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'PDF file.', content: new OA\MediaType(mediaType: 'application/pdf')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin, University Admin or Department Admin, or another department.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'No semester to report on.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Unknown semester.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function analyticsPdf(Request $request): Response
    {
        Gate::authorize('view-analytics');
        $semester = $this->analyticsApi->semester($request);
        $departmentId = $this->analyticsApi->departmentScope($request);

        if ($semester === null) {
            throw new BusinessRuleException('There is no semester to report on.');
        }

        $semester->loadMissing('academicYear');
        $university = University::query()->where('is_current', true)->first();
        $department = $this->analyticsApi->departmentSummary($departmentId);

        $overview = $this->analytics->overview($semester, $departmentId);
        $enrollment = $this->analytics->enrollment($semester, $departmentId);
        $academic = $this->analytics->academic($semester, $departmentId);
        $administrative = $this->analytics->administrative($departmentId);

        $this->audited('analytics_pdf', ['semester_id' => $semester->id, 'department_id' => $departmentId], 1);

        $filename = 'analytics-report-'.($department ? str($department['code'])->slug().'-' : '').str($semester->academicYear?->code.' '.$semester->name)->slug().'.pdf';

        $pdf = Pdf::loadView('analytics.pdf', [
            'semester' => $semester,
            'university' => $university,
            'department' => $department,
            'generatedAt' => now()->format('Y-m-d H:i'),
            'overview' => $overview,
            'enrollment' => $enrollment,
            'academic' => $academic,
            'administrative' => $administrative,
        ])->setPaper('a4')->setOption('isFontSubsettingEnabled', true);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * @return list<array{0: string, 1: string, 2: int}>
     */
    private function workloadRows(?int $departmentId = null): array
    {
        $admin = $this->analytics->administrative($departmentId);
        $rows = [];

        foreach (['documents' => 'Document requests', 'internships' => 'Internships', 'invoices' => 'Invoices'] as $key => $area) {
            // A department's workload has no invoices (finance is university-wide).
            foreach ($admin[$key] ?? [] as $row) {
                $rows[] = [$area, $row['status'], $row['total']];
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function audited(string $what, array $filters, int $rows): void
    {
        $this->audit->record("export.{$what}", after: ['filters' => array_filter($filters, fn ($v) => $v !== null && $v !== ''), 'rows' => $rows]);
    }
}
