<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\StudentDashboardService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Student academic dashboard (module 9.15).
 */
class StudentDashboardController extends Controller
{
    public function __construct(
        private readonly StudentDashboardService $dashboard,
    ) {}

    #[OA\Get(
        path: '/students/{student}/dashboard',
        summary: "A student's academic dashboard",
        description: 'GPA, credits, current-semester attendance, today\'s classes, upcoming assignments and exams, and recent approved grades. Staff may read any student; a student only their own.',
        operationId: 'getStudentDashboard',
        tags: ['People'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'student', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Dashboard.', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/StudentDashboard')])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not staff or the student themself.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function __invoke(Student $student): JsonResponse
    {
        $this->authorize('view', $student);

        return response()->json(['data' => $this->dashboard->build($student)]);
    }
}
