<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lecturer;
use App\Services\LecturerDashboardService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Lecturer dashboard (`docs/35_Lecturer-Dashboard-Report.md`).
 */
class LecturerDashboardController extends Controller
{
    public function __construct(
        private readonly LecturerDashboardService $dashboard,
    ) {}

    #[OA\Get(
        path: '/lecturers/{lecturer}/dashboard',
        summary: "A lecturer's teaching dashboard",
        description: "Today's classes, registers not yet taken, submissions to grade, upcoming exams and the grade-sheet progress of the lecturer's sections in the current semester. The lecturer themself, or staff who may view the lecturer (a Department Admin within their department).",
        operationId: 'getLecturerDashboard',
        tags: ['People'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'lecturer', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Dashboard.', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/LecturerDashboard')])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not the lecturer themself or staff who may view them.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function __invoke(Lecturer $lecturer): JsonResponse
    {
        $this->authorize('view', $lecturer);

        return response()->json(['data' => $this->dashboard->build($lecturer)]);
    }
}
