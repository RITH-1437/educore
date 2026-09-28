<?php

namespace App\Http\Controllers\Api;

use App\Dto\AcademicYear\AcademicYearListFilters;
use App\Enums\AcademicYearStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeAcademicYearStatusRequest;
use App\Http\Requests\StoreAcademicYearRequest;
use App\Http\Requests\UpdateAcademicYearRequest;
use App\Http\Resources\AcademicYearResource;
use App\Models\AcademicYear;
use App\Services\AcademicYearService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Academic year endpoints.
 *
 * All routes are protected by `auth:sanctum` and the `role:super-admin,
 * role:university-admin` middleware, so every operation documents both 401 and
 * 403 responses.
 */
class AcademicYearController extends Controller
{
    public function __construct(
        private readonly AcademicYearService $academicYears,
    ) {}

    #[OA\Get(
        path: '/academic-years',
        summary: 'List academic years',
        description: 'Returns a paginated list of academic years, newest first. Supports case-insensitive search over code and name, filtering by status, whitelisted sorting and a capped page size.',
        operationId: 'listAcademicYears',
        tags: ['Academic Years'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(
                name: 'search',
                description: 'Case-insensitive partial match against `code` or `name`.',
                schema: new OA\Schema(type: 'string'),
                example: '2026'
            ),
            new OA\QueryParameter(
                name: 'filters[status]',
                description: 'Filter by planned, active, or completed. An unrecognized value is not rejected as validation; it is applied as a filter and normally returns an empty collection.',
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\QueryParameter(
                name: 'sort_by',
                description: 'Whitelisted sort column.',
                schema: new OA\Schema(type: 'string', enum: AcademicYearListFilters::SORTABLE, default: 'start_date'),
            ),
            new OA\QueryParameter(
                name: 'sort_dir',
                description: 'Sort direction.',
                schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc'),
            ),
            new OA\QueryParameter(
                name: 'per_page',
                description: 'Number of records per page (defaults to 15, max 100).',
                schema: new OA\Schema(type: 'integer', format: 'int32', default: 15, maximum: 100),
            ),
            new OA\QueryParameter(
                name: 'page',
                description: 'Page number for the paginated collection.',
                schema: new OA\Schema(type: 'integer', format: 'int32', default: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated collection of academic years.',
                content: new OA\JsonContent(ref: '#/components/schemas/AcademicYearCollection')
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'Authenticated but not a Super Admin or University Admin.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', AcademicYear::class);

        $academicYears = $this->academicYears->paginate(AcademicYearListFilters::fromInput($request->query()));

        return AcademicYearResource::collection($academicYears);
    }

    #[OA\Post(
        path: '/academic-years',
        summary: 'Create an academic year',
        operationId: 'createAcademicYear',
        tags: ['Academic Years'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreAcademicYearRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Academic year created.',
                content: new OA\JsonContent(ref: '#/components/schemas/AcademicYearResourceResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'Authenticated but not a Super Admin or University Admin.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 409,
                description: 'Business rule violated (e.g. only an active year can be current).',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed.',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    public function store(StoreAcademicYearRequest $request): JsonResponse
    {
        $this->authorize('create', AcademicYear::class);

        $academicYear = $this->academicYears->create($request->validated());

        return (new AcademicYearResource($academicYear))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/academic-years/{academicYear}',
        summary: 'Fetch an academic year',
        operationId: 'getAcademicYear',
        tags: ['Academic Years'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'academicYear',
                description: 'Identifier of the academic year.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The requested academic year.',
                content: new OA\JsonContent(ref: '#/components/schemas/AcademicYearResourceResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'Authenticated but not a Super Admin or University Admin.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Academic year not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function show(AcademicYear $academicYear): AcademicYearResource
    {
        $this->authorize('view', $academicYear);

        return new AcademicYearResource($academicYear->loadCount('semesters'));
    }

    #[OA\Put(
        path: '/academic-years/{academicYear}',
        summary: 'Update an academic year',
        description: 'Status moves are validated: an academic year only advances planned -> active -> completed, and only one year can be current.',
        operationId: 'updateAcademicYear',
        tags: ['Academic Years'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'academicYear',
                description: 'Identifier of the academic year.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateAcademicYearRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'The updated academic year.',
                content: new OA\JsonContent(ref: '#/components/schemas/AcademicYearResourceResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'Authenticated but not a Super Admin or University Admin.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Academic year not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 409,
                description: 'Illegal status transition.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed.',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    #[OA\Patch(
        path: '/academic-years/{academicYear}',
        summary: 'Update an academic year (PATCH)',
        description: 'Uses the same validation as PUT: `code`, `name`, `start_date`, and `end_date` remain required.',
        operationId: 'patchAcademicYear',
        tags: ['Academic Years'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'academicYear',
                description: 'Identifier of the academic year.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateAcademicYearRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'The updated academic year.',
                content: new OA\JsonContent(ref: '#/components/schemas/AcademicYearResourceResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'Authenticated but not a Super Admin or University Admin.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Academic year not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 409,
                description: 'Illegal status transition.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed.',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    public function update(UpdateAcademicYearRequest $request, AcademicYear $academicYear): AcademicYearResource
    {
        $this->authorize('update', $academicYear);

        $this->academicYears->update($academicYear, $request->validated());

        return new AcademicYearResource($academicYear->refresh()->loadCount('semesters'));
    }

    #[OA\Post(
        path: '/academic-years/{academicYear}/status',
        summary: 'Change an academic year status',
        description: 'Applies a status transition. Only planned -> active and active -> completed are accepted; anything else answers 409.',
        operationId: 'changeAcademicYearStatus',
        tags: ['Academic Years'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'academicYear',
                description: 'Identifier of the academic year.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ChangeAcademicYearStatusRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'The academic year with its new status.',
                content: new OA\JsonContent(ref: '#/components/schemas/AcademicYearResourceResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'Authenticated but not a Super Admin or University Admin.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Academic year not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 409,
                description: 'Illegal status transition.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed.',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    public function changeStatus(ChangeAcademicYearStatusRequest $request, AcademicYear $academicYear): AcademicYearResource
    {
        $this->authorize('update', $academicYear);

        $this->academicYears->changeStatus(
            $academicYear,
            AcademicYearStatus::from($request->validated('status')),
        );

        return new AcademicYearResource($academicYear->refresh()->loadCount('semesters'));
    }

    #[OA\Delete(
        path: '/academic-years/{academicYear}',
        summary: 'Delete an academic year',
        description: 'Hard delete. Refused with 409 while the year is current or still has semesters.',
        operationId: 'deleteAcademicYear',
        tags: ['Academic Years'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'academicYear',
                description: 'Identifier of the academic year.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Academic year deleted.'),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'Authenticated but not a Super Admin or University Admin.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Academic year not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 409,
                description: 'The year is current or still has semesters.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function destroy(AcademicYear $academicYear): JsonResponse
    {
        $this->authorize('delete', $academicYear);

        $this->academicYears->delete($academicYear);

        return response()->json(null, 204);
    }
}
