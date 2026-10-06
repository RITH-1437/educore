<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Semester;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Analytics (module 9.23) — Super Admin / University Admin for the whole
 * university or one department (`department_id`), a Department Admin for their
 * own department only (report 47). Academic and enrollment endpoints take
 * `semester_id` (default: the current open semester); administrative figures
 * are the current workload.
 */
class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analytics,
    ) {}

    #[OA\Get(
        path: '/analytics/overview',
        summary: 'Headline numbers for a semester',
        description: 'Active students and lecturers, sections, enrollments, attendance rate, approved grades and pass rate, average semester GPA. Rates are null when there is nothing to measure. For a department: students, enrollments and GPA of its students; sections, attendance and grades of its courses.',
        operationId: 'analyticsOverview',
        tags: ['Analytics'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'semester_id', required: false, schema: new OA\Schema(type: 'integer', format: 'int64')),
            new OA\QueryParameter(name: 'department_id', required: false, description: 'Managers: limit to one department. A Department Admin always gets their own (another id: 403).', schema: new OA\Schema(type: 'integer', format: 'int64')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Overview.', content: new OA\JsonContent(ref: '#/components/schemas/AnalyticsOverviewResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin, University Admin or Department Admin, or another department.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Unknown semester or department.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function overview(Request $request): JsonResponse
    {
        return $this->respond($request, fn (Semester $s, ?int $d) => $this->analytics->overview($s, $d));
    }

    #[OA\Get(
        path: '/analytics/enrollment',
        summary: 'Enrollment per program and per status for a semester',
        description: 'For a department: its programs and its students\' enrollments.',
        operationId: 'analyticsEnrollment',
        tags: ['Analytics'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'semester_id', required: false, schema: new OA\Schema(type: 'integer', format: 'int64')),
            new OA\QueryParameter(name: 'department_id', required: false, description: 'Managers: limit to one department. A Department Admin always gets their own (another id: 403).', schema: new OA\Schema(type: 'integer', format: 'int64')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Enrollment analytics.', content: new OA\JsonContent(ref: '#/components/schemas/AnalyticsEnrollmentResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin, University Admin or Department Admin, or another department.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Unknown semester or department.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function enrollment(Request $request): JsonResponse
    {
        return $this->respond($request, fn (Semester $s, ?int $d) => $this->analytics->enrollment($s, $d));
    }

    #[OA\Get(
        path: '/analytics/academic',
        summary: 'Academic performance for a semester',
        description: 'Letter-grade distribution on the active scale, semester-GPA bands, attendance rate per course (lowest first) and results per course — approved / finalized grades only. For a department: grades and attendance of its courses, GPA bands of its students.',
        operationId: 'analyticsAcademic',
        tags: ['Analytics'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'semester_id', required: false, schema: new OA\Schema(type: 'integer', format: 'int64')),
            new OA\QueryParameter(name: 'department_id', required: false, description: 'Managers: limit to one department. A Department Admin always gets their own (another id: 403).', schema: new OA\Schema(type: 'integer', format: 'int64')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Academic analytics.', content: new OA\JsonContent(ref: '#/components/schemas/AnalyticsAcademicResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin, University Admin or Department Admin, or another department.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Unknown semester or department.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function academic(Request $request): JsonResponse
    {
        return $this->respond($request, fn (Semester $s, ?int $d) => $this->analytics->academic($s, $d));
    }

    #[OA\Get(
        path: '/analytics/administrative',
        summary: 'Current administrative workload',
        description: 'Document requests, internships and invoices by status, and finance totals per currency (never summed across currencies). For a department: its students\' document requests and internships; `invoices` and `finance` are null (university-wide only).',
        operationId: 'analyticsAdministrative',
        tags: ['Analytics'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'department_id', required: false, description: 'Managers: limit to one department. A Department Admin always gets their own (another id: 403).', schema: new OA\Schema(type: 'integer', format: 'int64')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Administrative analytics.', content: new OA\JsonContent(ref: '#/components/schemas/AnalyticsAdministrativeResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin, University Admin or Department Admin, or another department.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Unknown department.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function administrative(Request $request): JsonResponse
    {
        Gate::authorize('view-analytics');
        $department = $this->departmentScope($request);

        return response()->json([
            'department' => $this->departmentSummary($department),
            'data' => $this->analytics->administrative($department),
        ]);
    }

    #[OA\Get(
        path: '/analytics/trends',
        summary: 'Headline figures across the latest semesters',
        description: 'Enrollments, students enrolled, attendance rate, pass rate and average semester GPA for each of the latest six semesters (oldest first), on the same definitions as the overview. Rates are null when there is nothing to measure.',
        operationId: 'analyticsTrends',
        tags: ['Analytics'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'department_id', required: false, description: 'Managers: limit to one department. A Department Admin always gets their own (another id: 403).', schema: new OA\Schema(type: 'integer', format: 'int64')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Trends.', content: new OA\JsonContent(ref: '#/components/schemas/AnalyticsTrendsResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin, University Admin or Department Admin, or another department.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Unknown department.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function trends(Request $request): JsonResponse
    {
        Gate::authorize('view-analytics');
        $department = $this->departmentScope($request);

        return response()->json([
            'department' => $this->departmentSummary($department),
            'data' => $this->analytics->trends($department),
        ]);
    }

    /** The requested semester, or the default one (null when none exist). */
    public function semester(Request $request): ?Semester
    {
        $id = $request->validate(['semester_id' => ['nullable', 'integer', Rule::exists('semesters', 'id')]])['semester_id'] ?? null;

        return $id ? Semester::query()->find($id) : $this->analytics->defaultSemester();
    }

    /**
     * The department the figures are limited to. A Department Admin always gets
     * their own — another `department_id`, or having no department at all,
     * answers 403 — and a manager gets the one they pick, or null for the whole
     * university.
     */
    public function departmentScope(Request $request): ?int
    {
        $id = $request->validate(['department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')]])['department_id'] ?? null;
        $own = $request->user()->departmentScope();

        if ($own !== null) {
            abort_if($own === 0, 403, 'You are not assigned to a department.');
            abort_if($id !== null && (int) $id !== $own, 403, 'You can only see your own department\'s analytics.');

            return $own;
        }

        return $id === null ? null : (int) $id;
    }

    /**
     * @return array{id: int, name: string, code: string}|null
     */
    public function departmentSummary(?int $departmentId): ?array
    {
        $department = $departmentId ? Department::query()->find($departmentId, ['id', 'name', 'code']) : null;

        return $department ? ['id' => $department->id, 'name' => $department->name, 'code' => $department->code] : null;
    }

    /**
     * @param  callable(Semester, ?int): array<string, mixed>  $build
     */
    private function respond(Request $request, callable $build): JsonResponse
    {
        Gate::authorize('view-analytics');
        $semester = $this->semester($request);
        $department = $this->departmentScope($request);

        return response()->json([
            'semester' => $semester ? ['id' => $semester->id, 'name' => trim(($semester->academicYear?->code ?? '').' '.$semester->name)] : null,
            'department' => $this->departmentSummary($department),
            'data' => $semester ? $build($semester, $department) : null,
        ]);
    }
}
