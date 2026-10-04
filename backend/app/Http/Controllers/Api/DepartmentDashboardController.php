<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\DepartmentDashboardService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Department dashboard (`docs/39_Department-Only-Structure-Report.md`, formerly the
 * faculty dashboard of report 34).
 */
class DepartmentDashboardController extends Controller
{
    public function __construct(
        private readonly DepartmentDashboardService $dashboard,
    ) {}

    #[OA\Get(
        path: '/departments/{department}/dashboard',
        summary: "A department's dashboard",
        description: "Document requests and internships of the department's students waiting to be processed, and the department's headline numbers for the current semester. Super Admin and University Admin may read any department; a Department Admin only their own.",
        operationId: 'getDepartmentDashboard',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'department', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Dashboard.', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/DepartmentDashboard')])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: "Not a manager or this department's Department Admin.", content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function __invoke(Department $department): JsonResponse
    {
        $this->authorize('view', $department);

        return response()->json(['data' => $this->dashboard->build($department)]);
    }
}
