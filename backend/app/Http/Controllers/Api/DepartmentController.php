<?php

namespace App\Http\Controllers\Api;

use App\Dto\UniversityStructure\DepartmentListFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Models\Faculty;
use App\Services\DepartmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Department endpoints.
 *
 * A department always belongs to exactly one faculty, so `faculty_id` is part of
 * the resource and `filters[faculty_id]` scopes the list
 * (`skills/faculty-department/SKILL.md` §5).
 *
 * Protected by `auth:sanctum` and `role:super-admin,university-admin`, so every
 * operation documents both 401 and 403.
 */
class DepartmentController extends Controller
{
    public function __construct(
        private readonly DepartmentService $departments,
    ) {}

    #[OA\Get(
        path: '/departments',
        summary: 'List departments',
        description: 'Returns a paginated list of departments. Supports case-insensitive search over code and name, scoping by faculty, filtering by active state, whitelisted sorting and a capped page size.',
        operationId: 'listDepartments',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(
                name: 'search',
                description: 'Case-insensitive partial match against `code` or `name`.',
                schema: new OA\Schema(type: 'string'),
                example: 'Computer'
            ),
            new OA\QueryParameter(
                name: 'filters[faculty_id]',
                description: 'Only departments belonging to this faculty.',
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
            new OA\QueryParameter(
                name: 'filters[is_active]',
                description: 'Filter by active state.',
                schema: new OA\Schema(type: 'boolean'),
            ),
            new OA\QueryParameter(
                name: 'sort_by',
                description: 'Whitelisted sort column.',
                schema: new OA\Schema(type: 'string', enum: DepartmentListFilters::SORTABLE, default: 'name'),
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
                description: 'Paginated collection of departments.',
                content: new OA\JsonContent(ref: '#/components/schemas/DepartmentCollection')
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
        $this->authorize('viewAny', Department::class);

        $departments = $this->departments->paginate(DepartmentListFilters::fromInput($request->query()));

        return DepartmentResource::collection($departments);
    }

    #[OA\Post(
        path: '/departments',
        summary: 'Create a department',
        description: '`faculty_id` is required here. Department names must be unique within their faculty.',
        operationId: 'createDepartment',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreDepartmentRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Department created.',
                content: new OA\JsonContent(ref: '#/components/schemas/DepartmentResourceResponse')
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
    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $this->authorize('create', Department::class);

        $facultyId = $request->validated('faculty_id') ?? $request->facultyKey();

        abort_if($facultyId === null, 422, 'A faculty_id is required to create a department.');

        $department = $this->departments->create([
            ...$request->safe()->except('faculty_id'),
            'faculty_id' => $facultyId,
        ]);

        return (new DepartmentResource($department->load('faculty:id,code,name,university_id')))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/departments/{department}',
        summary: 'Fetch a department',
        operationId: 'getDepartment',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'department',
                description: 'Identifier of the department.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The requested department.',
                content: new OA\JsonContent(ref: '#/components/schemas/DepartmentResourceResponse')
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
                description: 'Department not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function show(Department $department): DepartmentResource
    {
        $this->authorize('view', $department);

        return new DepartmentResource($department->load('faculty:id,code,name,university_id'));
    }

    #[OA\Put(
        path: '/departments/{department}',
        summary: 'Update a department',
        description: 'Changing `faculty_id` moves the department to another faculty; its children keep pointing at the same department id, so their references stay valid.',
        operationId: 'updateDepartment',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'department',
                description: 'Identifier of the department.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateDepartmentRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'The updated department.',
                content: new OA\JsonContent(ref: '#/components/schemas/DepartmentResourceResponse')
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
                description: 'Department not found.',
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
        path: '/departments/{department}',
        summary: 'Update a department (PATCH)',
        description: 'Uses the same validation as PUT: `code` and `name` remain required.',
        operationId: 'patchDepartment',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'department',
                description: 'Identifier of the department.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateDepartmentRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'The updated department.',
                content: new OA\JsonContent(ref: '#/components/schemas/DepartmentResourceResponse')
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
                description: 'Department not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed.',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    public function update(UpdateDepartmentRequest $request, Department $department): DepartmentResource
    {
        $this->authorize('update', $department);

        $this->departments->update($department, $request->validated());

        return new DepartmentResource($department->refresh()->load('faculty:id,code,name,university_id'));
    }

    #[OA\Post(
        path: '/departments/{department}/archive',
        summary: 'Archive a department',
        description: 'Sets `is_active` to false instead of deleting, so programs, courses and lecturers keep a valid parent.',
        operationId: 'archiveDepartment',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'department',
                description: 'Identifier of the department.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The archived department.',
                content: new OA\JsonContent(ref: '#/components/schemas/DepartmentResourceResponse')
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
                description: 'Department not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function archive(Department $department): DepartmentResource
    {
        $this->authorize('archive', $department);

        $this->departments->archive($department);

        return new DepartmentResource($department->refresh());
    }

    #[OA\Post(
        path: '/departments/{department}/reactivate',
        summary: 'Reactivate an archived department',
        operationId: 'reactivateDepartment',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'department',
                description: 'Identifier of the department.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The reactivated department.',
                content: new OA\JsonContent(ref: '#/components/schemas/DepartmentResourceResponse')
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
                description: 'Department not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function reactivate(Department $department): DepartmentResource
    {
        $this->authorize('archive', $department);

        $this->departments->reactivate($department);

        return new DepartmentResource($department->refresh());
    }

    #[OA\Delete(
        path: '/departments/{department}',
        summary: 'Delete a department',
        description: 'Soft delete. Refused with 409 while programs, courses or lecturers still reference the department; archive it instead.',
        operationId: 'deleteDepartment',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'department',
                description: 'Identifier of the department.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Department deleted.'),
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
                description: 'Department not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 409,
                description: 'The department is still referenced by programs, courses or lecturers.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function destroy(Department $department): JsonResponse
    {
        $this->authorize('delete', $department);

        $this->departments->delete($department);

        return response()->json(null, 204);
    }

    /**
     * Faculty → department tree for cascading selects
     * (`skills/faculty-department/SKILL.md` §6).
     */
    #[OA\Get(
        path: '/faculties-tree',
        summary: 'Faculty → department tree',
        description: 'Returns active faculties with their active departments, shaped for cascading dropdowns. Not paginated: the structure is small by nature.',
        operationId: 'getFacultyTree',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The faculty tree.',
                content: new OA\JsonContent(ref: '#/components/schemas/FacultyTreeCollection')
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
    public function tree(): JsonResponse
    {
        $this->authorize('viewAny', Faculty::class);

        $tree = Faculty::query()
            ->active()
            ->with(['departments' => fn ($query) => $query->active()])
            ->orderBy('name')
            ->get()
            ->map(fn (Faculty $faculty) => [
                'id' => $faculty->id,
                'code' => $faculty->code,
                'name' => $faculty->name,
                'is_active' => $faculty->is_active,
                'departments' => $faculty->departments->map(fn (Department $department) => [
                    'id' => $department->id,
                    'faculty_id' => $department->faculty_id,
                    'code' => $department->code,
                    'name' => $department->name,
                    'is_active' => $department->is_active,
                ])->all(),
            ])
            ->all();

        return response()->json(['data' => $tree]);
    }
}
