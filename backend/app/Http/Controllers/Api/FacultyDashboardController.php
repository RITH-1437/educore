<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Services\FacultyDashboardService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Faculty dashboard (`docs/34_Faculty-Admin-Dashboard-Report.md`).
 */
class FacultyDashboardController extends Controller
{
    public function __construct(
        private readonly FacultyDashboardService $dashboard,
    ) {}

    #[OA\Get(
        path: '/faculties/{faculty}/dashboard',
        summary: "A faculty's dashboard",
        description: "Document requests and internships of the faculty's students waiting to be processed, and the faculty's headline numbers for the current semester. Super Admin and University Admin may read any faculty; a Faculty Admin only their own.",
        operationId: 'getFacultyDashboard',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'faculty', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Dashboard.', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/FacultyDashboard')])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: "Not a manager or this faculty's Faculty Admin.", content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function __invoke(Faculty $faculty): JsonResponse
    {
        $this->authorize('view', $faculty);

        return response()->json(['data' => $this->dashboard->build($faculty)]);
    }
}
