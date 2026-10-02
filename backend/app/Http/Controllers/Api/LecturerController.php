<?php

namespace App\Http\Controllers\Api;

use App\Dto\People\LecturerListFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLecturerRequest;
use App\Http\Requests\UpdateLecturerRequest;
use App\Http\Resources\LecturerResource;
use App\Http\Resources\SectionResource;
use App\Models\Lecturer;
use App\Services\LecturerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Lecturer endpoints (module 9.3).
 *
 * List: Super Admin, University Admin, Faculty Admin. Show: the same, plus a
 * lecturer reading their own profile. Writes: Super Admin and University Admin.
 */
class LecturerController extends Controller
{
    public function __construct(
        private readonly LecturerService $lecturers,
    ) {}

    #[OA\Get(
        path: '/lecturers',
        summary: 'List lecturers',
        description: 'Paginated lecturer profiles with their account and department. Search covers staff number, names, specialization and email.',
        operationId: 'listLecturers',
        tags: ['People'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'search', description: 'Partial match on staff number, first/last name, specialization or email.', schema: new OA\Schema(type: 'string'), example: 'Sok'),
            new OA\QueryParameter(name: 'filters[faculty_id]', description: 'Lecturers whose department belongs to this faculty.', schema: new OA\Schema(type: 'integer', format: 'int64')),
            new OA\QueryParameter(name: 'filters[department_id]', description: 'Lecturers of this department.', schema: new OA\Schema(type: 'integer', format: 'int64')),
            new OA\QueryParameter(name: 'filters[employment_type]', description: 'Employment type.', schema: new OA\Schema(type: 'string', enum: Lecturer::EMPLOYMENT_TYPES)),
            new OA\QueryParameter(name: 'filters[is_active]', description: 'Active (1) or inactive (0) lecturers.', schema: new OA\Schema(type: 'boolean')),
            new OA\QueryParameter(name: 'sort_by', description: 'Whitelisted sort column.', schema: new OA\Schema(type: 'string', enum: LecturerListFilters::SORTABLE, default: 'last_name')),
            new OA\QueryParameter(name: 'sort_dir', description: 'Sort direction.', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'asc')),
            new OA\QueryParameter(name: 'per_page', description: 'Records per page (default 15, max 100).', schema: new OA\Schema(type: 'integer', format: 'int32', default: 15, maximum: 100)),
            new OA\QueryParameter(name: 'page', description: 'Page number.', schema: new OA\Schema(type: 'integer', format: 'int32', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated collection of lecturers.', content: new OA\JsonContent(ref: '#/components/schemas/LecturerCollection')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Role may not list lecturers.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Lecturer::class);

        return LecturerResource::collection(
            $this->lecturers->paginate(LecturerListFilters::fromInput($request->query()), $request->user())
        );
    }

    #[OA\Post(
        path: '/lecturers',
        summary: 'Create a lecturer',
        description: 'Send `user_id` to attach a profile to an existing Lecturer-role account without one, or `email` + `password` (+ `password_confirmation`) to create the account in the same transaction.',
        operationId: 'createLecturer',
        tags: ['People'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreLecturerRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Lecturer created.', content: new OA\JsonContent(ref: '#/components/schemas/LecturerResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'The Lecturer role is not configured.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function store(StoreLecturerRequest $request): JsonResponse
    {
        $this->authorize('create', Lecturer::class);

        $lecturer = $this->lecturers->create($request->validated());

        return (new LecturerResource($lecturer->load(['user', 'department.faculty:id,code,name'])))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/lecturers/{lecturer}',
        summary: 'Fetch a lecturer',
        description: 'Staff may fetch any lecturer; a lecturer may fetch only their own profile.',
        operationId: 'getLecturer',
        tags: ['People'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'lecturer', description: 'Identifier of the lecturer profile.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1)],
        responses: [
            new OA\Response(response: 200, description: 'The requested lecturer.', content: new OA\JsonContent(ref: '#/components/schemas/LecturerResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not allowed to view this lecturer.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Lecturer not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Lecturer $lecturer): LecturerResource
    {
        $this->authorize('view', $lecturer);

        return new LecturerResource($lecturer->load(['user', 'department.faculty:id,code,name']));
    }

    #[OA\Get(
        path: '/lecturers/{lecturer}/sections',
        summary: "A lecturer's teaching load",
        description: 'Sections the lecturer is assigned to, newest semester first. Staff may read any lecturer; a lecturer only their own.',
        operationId: 'getLecturerSections',
        tags: ['People'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'lecturer', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Assigned sections with offering and role.', content: new OA\JsonContent(ref: '#/components/schemas/SectionCollection')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not allowed to view this lecturer.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Lecturer not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function sections(Lecturer $lecturer): AnonymousResourceCollection
    {
        $this->authorize('view', $lecturer);

        return SectionResource::collection(
            $lecturer->sections()
                ->with(['offering.course:id,code,name,credits', 'offering.semester.academicYear:id,code', 'lecturers'])
                ->orderByDesc('section_lecturers.created_at')
                ->get()
        );
    }

    #[OA\Put(
        path: '/lecturers/{lecturer}',
        summary: 'Update a lecturer',
        description: 'Updates the profile; the linked account name follows first/last name, and `email`/`phone` update the account.',
        operationId: 'updateLecturer',
        tags: ['People'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'lecturer', description: 'Identifier of the lecturer profile.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1)],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateLecturerRequest')),
        responses: [
            new OA\Response(response: 200, description: 'The updated lecturer.', content: new OA\JsonContent(ref: '#/components/schemas/LecturerResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Lecturer not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    #[OA\Patch(
        path: '/lecturers/{lecturer}',
        summary: 'Update a lecturer (PATCH)',
        description: 'Same validation as PUT.',
        operationId: 'patchLecturer',
        tags: ['People'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'lecturer', description: 'Identifier of the lecturer profile.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1)],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateLecturerRequest')),
        responses: [
            new OA\Response(response: 200, description: 'The updated lecturer.', content: new OA\JsonContent(ref: '#/components/schemas/LecturerResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Lecturer not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function update(UpdateLecturerRequest $request, Lecturer $lecturer): LecturerResource
    {
        $this->authorize('update', $lecturer);

        $this->lecturers->update($lecturer, $request->validated());

        return new LecturerResource($lecturer->refresh()->load(['user', 'department.faculty:id,code,name']));
    }

    #[OA\Post(
        path: '/lecturers/{lecturer}/deactivate',
        summary: 'Deactivate a lecturer',
        description: 'Marks the profile and its linked account inactive.',
        operationId: 'deactivateLecturer',
        tags: ['People'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'lecturer', description: 'Identifier of the lecturer profile.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1)],
        responses: [
            new OA\Response(response: 200, description: 'The deactivated lecturer.', content: new OA\JsonContent(ref: '#/components/schemas/LecturerResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Lecturer not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function deactivate(Lecturer $lecturer): LecturerResource
    {
        $this->authorize('deactivate', $lecturer);

        return new LecturerResource($this->lecturers->deactivate($lecturer)->load('user'));
    }

    #[OA\Post(
        path: '/lecturers/{lecturer}/reactivate',
        summary: 'Reactivate a lecturer',
        description: 'Marks the profile and its linked account active.',
        operationId: 'reactivateLecturer',
        tags: ['People'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'lecturer', description: 'Identifier of the lecturer profile.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1)],
        responses: [
            new OA\Response(response: 200, description: 'The reactivated lecturer.', content: new OA\JsonContent(ref: '#/components/schemas/LecturerResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Lecturer not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function reactivate(Lecturer $lecturer): LecturerResource
    {
        $this->authorize('deactivate', $lecturer);

        return new LecturerResource($this->lecturers->reactivate($lecturer)->load('user'));
    }

    #[OA\Delete(
        path: '/lecturers/{lecturer}',
        summary: 'Delete a lecturer profile',
        description: 'Removes the profile and marks the linked account inactive (it is not deleted). Refused with 409 while the lecturer is assigned to sections; deactivate instead.',
        operationId: 'deleteLecturer',
        tags: ['People'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'lecturer', description: 'Identifier of the lecturer profile.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1)],
        responses: [
            new OA\Response(response: 204, description: 'Profile deleted.'),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Lecturer not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'The lecturer is assigned to sections.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Lecturer $lecturer): JsonResponse
    {
        $this->authorize('delete', $lecturer);

        $this->lecturers->delete($lecturer);

        return response()->json(null, 204);
    }
}
