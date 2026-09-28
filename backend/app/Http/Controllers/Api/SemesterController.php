<?php

namespace App\Http\Controllers\Api;

use App\Enums\SemesterStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeSemesterStatusRequest;
use App\Http\Requests\StoreSemesterRequest;
use App\Http\Resources\SemesterResource;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Services\SemesterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Semester endpoints, nested under their academic year.
 *
 * Semesters are a genuine child collection, so they hang off
 * `/api/academic-years/{academic_year}/semesters`.
 */
class SemesterController extends Controller
{
    public function __construct(
        private readonly SemesterService $semesters,
    ) {}

    #[OA\Get(
        path: '/academic-years/{academic_year}/semesters',
        summary: 'List the semesters of an academic year',
        operationId: 'listSemesters',
        tags: ['Semesters'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'academic_year',
                description: 'Identifier of the academic year.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Semesters ordered by sequence.',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Semester'))
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
    public function index(AcademicYear $academicYear): AnonymousResourceCollection
    {
        $this->authorize('view', $academicYear);

        return SemesterResource::collection($academicYear->semesters()->get());
    }

    #[OA\Post(
        path: '/academic-years/{academic_year}/semesters',
        summary: 'Create a semester',
        description: 'A semester cannot be created inside a completed academic year, and its dates must stay within that year.',
        operationId: 'createSemester',
        tags: ['Semesters'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'academic_year',
                description: 'Identifier of the academic year.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreSemesterRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Semester created.',
                content: new OA\JsonContent(ref: '#/components/schemas/Semester')
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
                description: 'Business rule violated (completed year, dates outside the year).',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed.',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    public function store(StoreSemesterRequest $request, AcademicYear $academicYear): JsonResponse
    {
        $this->authorize('create', Semester::class);

        $semester = $this->semesters->create($academicYear, $request->validated());

        return (new SemesterResource($semester))->response()->setStatusCode(201);
    }

    #[OA\Post(
        path: '/academic-years/{academic_year}/semesters/{semester}/status',
        summary: 'Change a semester status',
        description: 'Applies a status transition. Only planned -> open -> closed -> completed is accepted; anything else answers 409.',
        operationId: 'changeSemesterStatus',
        tags: ['Semesters'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'academic_year',
                description: 'Identifier of the academic year.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
            new OA\PathParameter(
                name: 'semester',
                description: 'Identifier of the semester.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ChangeSemesterStatusRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'The semester with its new status.',
                content: new OA\JsonContent(ref: '#/components/schemas/Semester')
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
                description: 'Semester not found.',
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
    public function changeStatus(ChangeSemesterStatusRequest $request, AcademicYear $academicYear, Semester $semester): SemesterResource
    {
        $this->authorize('update', $semester);

        $this->assertBelongsToYear($academicYear, $semester);

        $this->semesters->changeStatus($semester, SemesterStatus::from($request->validated('status')));

        return new SemesterResource($semester->refresh());
    }

    #[OA\Delete(
        path: '/academic-years/{academic_year}/semesters/{semester}',
        summary: 'Delete a semester',
        description: 'Refused with 409 once the semester has course offerings.',
        operationId: 'deleteSemester',
        tags: ['Semesters'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'academic_year',
                description: 'Identifier of the academic year.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
            new OA\PathParameter(
                name: 'semester',
                description: 'Identifier of the semester.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Semester deleted.'),
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
                description: 'Semester not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 409,
                description: 'The semester already has course offerings.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function destroy(AcademicYear $academicYear, Semester $semester): JsonResponse
    {
        $this->authorize('delete', $semester);

        $this->assertBelongsToYear($academicYear, $semester);

        $this->semesters->delete($semester);

        return response()->json(null, 204);
    }

    /**
     * A nested semester must belong to the academic year in the URL.
     */
    private function assertBelongsToYear(AcademicYear $academicYear, Semester $semester): void
    {
        abort_unless(
            $semester->academic_year_id === $academicYear->getKey(),
            404,
            'Semester not found in this academic year.',
        );
    }
}
