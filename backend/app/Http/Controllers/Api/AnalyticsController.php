<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Institutional analytics (module 9.23) — Super Admin / University Admin.
 * Academic and enrollment endpoints take `semester_id` (default: the current
 * open semester); administrative figures are the current workload.
 */
class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analytics,
    ) {}

    #[OA\Get(
        path: '/analytics/overview',
        summary: 'Headline numbers for a semester',
        description: 'Active students and lecturers, sections, enrollments, attendance rate, approved grades and pass rate, average semester GPA. Rates are null when there is nothing to measure.',
        operationId: 'analyticsOverview',
        tags: ['Analytics'],
        security: [['sanctum' => []]],
        parameters: [new OA\QueryParameter(name: 'semester_id', required: false, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Overview.', content: new OA\JsonContent(ref: '#/components/schemas/AnalyticsOverviewResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Unknown semester.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function overview(Request $request): JsonResponse
    {
        return $this->respond($request, fn (Semester $s) => $this->analytics->overview($s));
    }

    #[OA\Get(
        path: '/analytics/enrollment',
        summary: 'Enrollment per program and per status for a semester',
        operationId: 'analyticsEnrollment',
        tags: ['Analytics'],
        security: [['sanctum' => []]],
        parameters: [new OA\QueryParameter(name: 'semester_id', required: false, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Enrollment analytics.', content: new OA\JsonContent(ref: '#/components/schemas/AnalyticsEnrollmentResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Unknown semester.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function enrollment(Request $request): JsonResponse
    {
        return $this->respond($request, fn (Semester $s) => $this->analytics->enrollment($s));
    }

    #[OA\Get(
        path: '/analytics/academic',
        summary: 'Academic performance for a semester',
        description: 'Letter-grade distribution on the active scale, semester-GPA bands, attendance rate per course (lowest first) and results per course — approved / finalized grades only.',
        operationId: 'analyticsAcademic',
        tags: ['Analytics'],
        security: [['sanctum' => []]],
        parameters: [new OA\QueryParameter(name: 'semester_id', required: false, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Academic analytics.', content: new OA\JsonContent(ref: '#/components/schemas/AnalyticsAcademicResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Unknown semester.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function academic(Request $request): JsonResponse
    {
        return $this->respond($request, fn (Semester $s) => $this->analytics->academic($s));
    }

    #[OA\Get(
        path: '/analytics/administrative',
        summary: 'Current administrative workload',
        description: 'Document requests, internships and invoices by status, and finance totals per currency (never summed across currencies).',
        operationId: 'analyticsAdministrative',
        tags: ['Analytics'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Administrative analytics.', content: new OA\JsonContent(ref: '#/components/schemas/AnalyticsAdministrativeResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function administrative(): JsonResponse
    {
        Gate::authorize('view-analytics');

        return response()->json(['data' => $this->analytics->administrative()]);
    }

    /** The requested semester, or the default one (null when none exist). */
    public function semester(Request $request): ?Semester
    {
        $id = $request->validate(['semester_id' => ['nullable', 'integer', Rule::exists('semesters', 'id')]])['semester_id'] ?? null;

        return $id ? Semester::query()->find($id) : $this->analytics->defaultSemester();
    }

    /**
     * @param  callable(Semester): array<string, mixed>  $build
     */
    private function respond(Request $request, callable $build): JsonResponse
    {
        Gate::authorize('view-analytics');
        $semester = $this->semester($request);

        return response()->json([
            'semester' => $semester ? ['id' => $semester->id, 'name' => trim(($semester->academicYear?->code ?? '').' '.$semester->name)] : null,
            'data' => $semester ? $build($semester) : null,
        ]);
    }
}
