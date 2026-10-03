<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UniversityDashboardService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * University Admin dashboard (`docs/36_University-Admin-Dashboard-Report.md`).
 */
class UniversityDashboardController extends Controller
{
    public function __construct(
        private readonly UniversityDashboardService $dashboard,
    ) {}

    #[OA\Get(
        path: '/university/dashboard',
        summary: 'University-wide administration dashboard',
        description: 'Institution-wide document requests and internships waiting for processing, overdue invoices, and headline academic numbers for the current semester.',
        operationId: 'getUniversityDashboard',
        tags: ['Reports'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Dashboard.', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/UniversityDashboard')])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function __invoke(): JsonResponse
    {
        return response()->json(['data' => $this->dashboard->build()]);
    }
}
