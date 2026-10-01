<?php

namespace App\Http\Controllers\Api;

use App\Dto\UniversityStructure\FacultyListFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFacultyRequest;
use App\Http\Requests\UpdateFacultyRequest;
use App\Http\Resources\FacultyResource;
use App\Models\Faculty;
use App\Services\FacultyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Faculty endpoints.
 *
 * Departments are a separate resource under `/api/departments` with a
 * `filters[faculty_id]` scope (`skills/faculty-department/SKILL.md` §5), so
 * this controller stays about the faculty itself.
 *
 * Protected by `auth:sanctum` and `role:super-admin,university-admin`, so every
 * operation documents both 401 and 403.
 */
class FacultyController extends Controller
{
    public function __construct(
        private readonly FacultyService $faculties,
    ) {}

    #[OA\Get(
        path: '/faculties',
        summary: 'List faculties',
        description: 'Returns a paginated list of faculties with their department counts. Supports case-insensitive search over code and name, filtering by university and active state, whitelisted sorting and a capped page size.',
        operationId: 'listFaculties',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(
                name: 'search',
                description: 'Case-insensitive partial match against `code` or `name`.',
                schema: new OA\Schema(type: 'string'),
                example: 'Engineering'
            ),
            new OA\QueryParameter(
                name: 'filters[university_id]',
                description: 'Only faculties belonging to this university.',
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
            new OA\QueryParameter(
                name: 'filters[is_active]',
                description: 'Filter by active state. Archived faculties set `is_active` to false.',
                schema: new OA\Schema(type: 'boolean'),
            ),
            new OA\QueryParameter(
                name: 'sort_by',
                description: 'Whitelisted sort column.',
                schema: new OA\Schema(type: 'string', enum: FacultyListFilters::SORTABLE, default: 'name'),
            ),
            new OA\QueryParameter(
                name: 'sort_dir',
                description: 'Sort direction.',
                schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'asc'),
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
                description: 'Paginated collection of faculties.',
                content: new OA\JsonContent(ref: '#/components/schemas/FacultyCollection')
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
        $this->authorize('viewAny', Faculty::class);

        $faculties = $this->faculties->paginate(FacultyListFilters::fromInput($request->query()));

        return FacultyResource::collection($faculties);
    }

    #[OA\Post(
        path: '/faculties',
        summary: 'Create a faculty',
        operationId: 'createFaculty',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreFacultyRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Faculty created.',
                content: new OA\JsonContent(ref: '#/components/schemas/FacultyResourceResponse')
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
                response: 422,
                description: 'Validation failed.',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    public function store(StoreFacultyRequest $request): JsonResponse
    {
        $this->authorize('create', Faculty::class);

        $faculty = $this->faculties->create($request->validated());

        return (new FacultyResource($faculty->load('university:id,code,name')->loadCount('departments')))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/faculties/{faculty}',
        summary: 'Fetch a faculty',
        description: 'Loads the university relation, the department tree and department counts.',
        operationId: 'getFaculty',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'faculty',
                description: 'Identifier of the faculty.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The requested faculty.',
                content: new OA\JsonContent(ref: '#/components/schemas/FacultyResourceResponse')
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
                description: 'Faculty not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function show(Faculty $faculty): FacultyResource
    {
        $this->authorize('view', $faculty);

        return new FacultyResource(
            $faculty->load(['university:id,code,name', 'departments.faculty:id,code,name,university_id'])
                ->loadCount('departments')
        );
    }

    #[OA\Put(
        path: '/faculties/{faculty}',
        summary: 'Update a faculty',
        operationId: 'updateFaculty',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'faculty',
                description: 'Identifier of the faculty.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateFacultyRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'The updated faculty.',
                content: new OA\JsonContent(ref: '#/components/schemas/FacultyResourceResponse')
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
                description: 'Faculty not found.',
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
        path: '/faculties/{faculty}',
        summary: 'Update a faculty (PATCH)',
        description: 'Uses the same validation as PUT: `university_id`, `code` and `name` remain required.',
        operationId: 'patchFaculty',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'faculty',
                description: 'Identifier of the faculty.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateFacultyRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'The updated faculty.',
                content: new OA\JsonContent(ref: '#/components/schemas/FacultyResourceResponse')
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
                description: 'Faculty not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed.',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    public function update(UpdateFacultyRequest $request, Faculty $faculty): FacultyResource
    {
        $this->authorize('update', $faculty);

        $this->faculties->update($faculty, $request->validated());

        return new FacultyResource(
            $faculty->refresh()->load('university:id,code,name')->loadCount('departments')
        );
    }

    #[OA\Post(
        path: '/faculties/{faculty}/archive',
        summary: 'Archive a faculty',
        description: 'Sets `is_active` to false instead of deleting, so departments, programs, courses and lecturers keep a valid parent.',
        operationId: 'archiveFaculty',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'faculty',
                description: 'Identifier of the faculty.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The archived faculty.',
                content: new OA\JsonContent(ref: '#/components/schemas/FacultyResourceResponse')
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
                description: 'Faculty not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function archive(Faculty $faculty): FacultyResource
    {
        $this->authorize('archive', $faculty);

        $this->faculties->archive($faculty);

        return new FacultyResource($faculty->refresh()->loadCount('departments'));
    }

    #[OA\Post(
        path: '/faculties/{faculty}/reactivate',
        summary: 'Reactivate an archived faculty',
        operationId: 'reactivateFaculty',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'faculty',
                description: 'Identifier of the faculty.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The reactivated faculty.',
                content: new OA\JsonContent(ref: '#/components/schemas/FacultyResourceResponse')
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
                description: 'Faculty not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function reactivate(Faculty $faculty): FacultyResource
    {
        $this->authorize('archive', $faculty);

        $this->faculties->reactivate($faculty);

        return new FacultyResource($faculty->refresh()->loadCount('departments'));
    }

    #[OA\Delete(
        path: '/faculties/{faculty}',
        summary: 'Delete a faculty',
        description: 'Soft delete. Refused with 409 while the faculty still has departments; archive it instead.',
        operationId: 'deleteFaculty',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'faculty',
                description: 'Identifier of the faculty.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Faculty deleted.'),
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
                description: 'Faculty not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 409,
                description: 'The faculty still has departments.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function destroy(Faculty $faculty): JsonResponse
    {
        $this->authorize('delete', $faculty);

        $this->faculties->delete($faculty);

        return response()->json(null, 204);
    }
}
