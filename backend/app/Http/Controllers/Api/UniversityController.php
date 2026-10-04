<?php

namespace App\Http\Controllers\Api;

use App\Dto\UniversityStructure\UniversityListFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUniversityRequest;
use App\Http\Requests\UpdateUniversityRequest;
use App\Http\Resources\UniversityResource;
use App\Models\University;
use App\Services\UniversityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * University endpoints.
 *
 * Protected by `auth:sanctum` and `role:super-admin,university-admin`, so every
 * operation documents both 401 and 403.
 */
class UniversityController extends Controller
{
    public function __construct(
        private readonly UniversityService $universities,
    ) {}

    #[OA\Get(
        path: '/universities',
        summary: 'List universities',
        description: 'Returns a paginated list of universities. Supports case-insensitive search over code, name and short name, filtering by the current flag, whitelisted sorting and a capped page size.',
        operationId: 'listUniversities',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(
                name: 'search',
                description: 'Case-insensitive partial match against `code`, `name` or `short_name`.',
                schema: new OA\Schema(type: 'string'),
                example: 'ITC'
            ),
            new OA\QueryParameter(
                name: 'filters[is_current]',
                description: 'Filter by the current-university flag. At most one university holds it.',
                schema: new OA\Schema(type: 'boolean'),
            ),
            new OA\QueryParameter(
                name: 'sort_by',
                description: 'Whitelisted sort column.',
                schema: new OA\Schema(type: 'string', enum: UniversityListFilters::SORTABLE, default: 'name'),
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
                description: 'Paginated collection of universities.',
                content: new OA\JsonContent(ref: '#/components/schemas/UniversityCollection')
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
        $this->authorize('viewAny', University::class);

        $universities = $this->universities->paginate(UniversityListFilters::fromInput($request->query()));

        return UniversityResource::collection($universities);
    }

    #[OA\Post(
        path: '/universities',
        summary: 'Create a university',
        description: 'Setting `is_current` promotes this university and clears the flag on the previous one, so at most one is ever current.',
        operationId: 'createUniversity',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreUniversityRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'University created.',
                content: new OA\JsonContent(ref: '#/components/schemas/UniversityResourceResponse')
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
    public function store(StoreUniversityRequest $request): JsonResponse
    {
        $this->authorize('create', University::class);

        $university = $this->universities->create($request->validated());

        return (new UniversityResource($university->loadCount('departments')))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/universities/{university}',
        summary: 'Fetch a university',
        operationId: 'getUniversity',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'university',
                description: 'Identifier of the university.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The requested university.',
                content: new OA\JsonContent(ref: '#/components/schemas/UniversityResourceResponse')
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
                description: 'University not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function show(University $university): UniversityResource
    {
        $this->authorize('view', $university);

        return new UniversityResource($university->loadCount('departments'));
    }

    #[OA\Put(
        path: '/universities/{university}',
        summary: 'Update a university',
        operationId: 'updateUniversity',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'university',
                description: 'Identifier of the university.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateUniversityRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'The updated university.',
                content: new OA\JsonContent(ref: '#/components/schemas/UniversityResourceResponse')
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
                description: 'University not found.',
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
        path: '/universities/{university}',
        summary: 'Update a university (PATCH)',
        description: 'Uses the same validation as PUT: `code` and `name` remain required.',
        operationId: 'patchUniversity',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'university',
                description: 'Identifier of the university.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateUniversityRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'The updated university.',
                content: new OA\JsonContent(ref: '#/components/schemas/UniversityResourceResponse')
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
                description: 'University not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed.',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    public function update(UpdateUniversityRequest $request, University $university): UniversityResource
    {
        $this->authorize('update', $university);

        $this->universities->update($university, $request->validated());

        return new UniversityResource($university->refresh()->loadCount('departments'));
    }

    #[OA\Post(
        path: '/universities/{university}/current',
        summary: 'Mark a university as current',
        description: 'Clears `is_current` on every other university first, so at most one holds the flag.',
        operationId: 'makeUniversityCurrent',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'university',
                description: 'Identifier of the university.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The university with its new flag.',
                content: new OA\JsonContent(ref: '#/components/schemas/UniversityResourceResponse')
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
                description: 'University not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function makeCurrent(University $university): UniversityResource
    {
        $this->authorize('update', $university);

        $this->universities->makeCurrent($university);

        return new UniversityResource($university->refresh()->loadCount('departments'));
    }

    #[OA\Delete(
        path: '/universities/{university}',
        summary: 'Delete a university',
        description: 'Hard delete. Refused with 409 while the university is current or still has departments.',
        operationId: 'deleteUniversity',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'university',
                description: 'Identifier of the university.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'University deleted.'),
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
                description: 'University not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 409,
                description: 'The university is current or still has departments.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function destroy(University $university): JsonResponse
    {
        $this->authorize('delete', $university);

        $this->universities->delete($university);

        return response()->json(null, 204);
    }
}
