<?php

namespace App\Http\Controllers\Api;

use App\Dto\UniversityStructure\ProgramListFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProgramCourseRequest;
use App\Http\Requests\StoreProgramRequest;
use App\Http\Requests\UpdateProgramCourseRequest;
use App\Http\Requests\UpdateProgramRequest;
use App\Http\Resources\ProgramResource;
use App\Models\Course;
use App\Models\Program;
use App\Services\ProgramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Program endpoints (module 9.5).
 *
 * Reads are open to Super Admin, University Admin and Department Admin; writes to
 * Super Admin and University Admin — see `ProgramPolicy`. Every operation
 * documents 401 and 403.
 */
class ProgramController extends Controller
{
    public function __construct(
        private readonly ProgramService $programs,
    ) {}

    #[OA\Get(
        path: '/programs',
        summary: 'List programs',
        description: 'Paginated programs. Supports case-insensitive search over code and name, filtering by department, degree level and active state, whitelisted sorting and a capped page size.',
        operationId: 'listPrograms',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'search', description: 'Partial match against `code` or `name`.', schema: new OA\Schema(type: 'string'), example: 'Computer'),
            new OA\QueryParameter(name: 'filters[department_id]', description: 'Only programs of this department.', schema: new OA\Schema(type: 'integer', format: 'int64')),
            new OA\QueryParameter(name: 'filters[degree_level]', description: 'Degree level.', schema: new OA\Schema(type: 'string', enum: Program::DEGREE_LEVELS)),
            new OA\QueryParameter(name: 'filters[is_active]', description: 'Active (1) or archived (0) programs.', schema: new OA\Schema(type: 'boolean')),
            new OA\QueryParameter(name: 'sort_by', description: 'Whitelisted sort column.', schema: new OA\Schema(type: 'string', enum: ProgramListFilters::SORTABLE, default: 'name')),
            new OA\QueryParameter(name: 'sort_dir', description: 'Sort direction.', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'asc')),
            new OA\QueryParameter(name: 'per_page', description: 'Records per page (default 15, max 100).', schema: new OA\Schema(type: 'integer', format: 'int32', default: 15, maximum: 100)),
            new OA\QueryParameter(name: 'page', description: 'Page number.', schema: new OA\Schema(type: 'integer', format: 'int32', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated collection of programs.', content: new OA\JsonContent(ref: '#/components/schemas/ProgramCollection')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Role may not view programs.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Program::class);

        return ProgramResource::collection(
            $this->programs->paginate(ProgramListFilters::fromInput($request->query()), $request->user())
        );
    }

    #[OA\Post(
        path: '/programs',
        summary: 'Create a program',
        description: '`department_id` must reference an active department. `code` is globally unique; `name` is unique within the department.',
        operationId: 'createProgram',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreProgramRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Program created.', content: new OA\JsonContent(ref: '#/components/schemas/ProgramResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function store(StoreProgramRequest $request): JsonResponse
    {
        $this->authorize('create', Program::class);

        $program = $this->programs->create($request->validated());

        return (new ProgramResource($program->load('department:id,code,name')))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/programs/{program}',
        summary: 'Fetch a program',
        operationId: 'getProgram',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'program', description: 'Identifier of the program.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1)],
        responses: [
            new OA\Response(response: 200, description: 'The requested program.', content: new OA\JsonContent(ref: '#/components/schemas/ProgramResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Role may not view programs.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Program not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Program $program): ProgramResource
    {
        $this->authorize('view', $program);

        return new ProgramResource($program->load(['department:id,code,name', 'courses']));
    }

    #[OA\Post(
        path: '/programs/{program}/courses',
        summary: 'Add a course to the program curriculum',
        description: 'Archived courses and courses already in the curriculum are rejected with 422.',
        operationId: 'addProgramCourse',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'program', description: 'Identifier of the program.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1)],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreProgramCourseRequest')),
        responses: [
            new OA\Response(response: 201, description: 'The program with its updated curriculum.', content: new OA\JsonContent(ref: '#/components/schemas/ProgramResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Program not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function addCourse(StoreProgramCourseRequest $request, Program $program): JsonResponse
    {
        $this->authorize('update', $program);

        $this->programs->addCourse($program, Course::query()->findOrFail($request->validated('course_id')), $request->validated());

        return (new ProgramResource($program->load(['department:id,code,name', 'courses'])))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Patch(
        path: '/programs/{program}/courses/{course}',
        summary: 'Update a course placement in the curriculum',
        operationId: 'updateProgramCourse',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'program', description: 'Identifier of the program.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1),
            new OA\PathParameter(name: 'course', description: 'Identifier of the course.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateProgramCourseRequest')),
        responses: [
            new OA\Response(response: 200, description: 'The program with its updated curriculum.', content: new OA\JsonContent(ref: '#/components/schemas/ProgramResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Program not found, or the course is not in its curriculum.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function updateCourse(UpdateProgramCourseRequest $request, Program $program, Course $course): ProgramResource
    {
        $this->authorize('update', $program);
        abort_unless($this->programs->isInCurriculum($program, $course), 404, 'Course is not in this curriculum.');

        $this->programs->updateCourse($program, $course, $request->validated());

        return new ProgramResource($program->load(['department:id,code,name', 'courses']));
    }

    #[OA\Delete(
        path: '/programs/{program}/courses/{course}',
        summary: 'Remove a course from the program curriculum',
        description: 'Removes only the link; the course and its history are untouched.',
        operationId: 'removeProgramCourse',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'program', description: 'Identifier of the program.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1),
            new OA\PathParameter(name: 'course', description: 'Identifier of the course.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Course removed from the curriculum.'),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Program not found, or the course is not in its curriculum.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function removeCourse(Program $program, Course $course): JsonResponse
    {
        $this->authorize('update', $program);
        abort_unless($this->programs->isInCurriculum($program, $course), 404, 'Course is not in this curriculum.');

        $this->programs->removeCourse($program, $course);

        return response()->json(null, 204);
    }

    #[OA\Put(
        path: '/programs/{program}',
        summary: 'Update a program',
        description: 'Changing `department_id` moves the program to another department.',
        operationId: 'updateProgram',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'program', description: 'Identifier of the program.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1)],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateProgramRequest')),
        responses: [
            new OA\Response(response: 200, description: 'The updated program.', content: new OA\JsonContent(ref: '#/components/schemas/ProgramResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Program not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    #[OA\Patch(
        path: '/programs/{program}',
        summary: 'Update a program (PATCH)',
        description: 'Same validation as PUT: `code`, `name` and `degree_level` remain required.',
        operationId: 'patchProgram',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'program', description: 'Identifier of the program.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1)],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateProgramRequest')),
        responses: [
            new OA\Response(response: 200, description: 'The updated program.', content: new OA\JsonContent(ref: '#/components/schemas/ProgramResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Program not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function update(UpdateProgramRequest $request, Program $program): ProgramResource
    {
        $this->authorize('update', $program);

        $this->programs->update($program, $request->validated());

        return new ProgramResource($program->refresh()->load('department:id,code,name'));
    }

    #[OA\Post(
        path: '/programs/{program}/archive',
        summary: 'Archive a program',
        description: 'Sets `is_active` to false instead of deleting, so students and curriculum keep a valid program.',
        operationId: 'archiveProgram',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'program', description: 'Identifier of the program.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1)],
        responses: [
            new OA\Response(response: 200, description: 'The archived program.', content: new OA\JsonContent(ref: '#/components/schemas/ProgramResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Program not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function archive(Program $program): ProgramResource
    {
        $this->authorize('archive', $program);

        $this->programs->archive($program);

        return new ProgramResource($program->refresh());
    }

    #[OA\Post(
        path: '/programs/{program}/reactivate',
        summary: 'Reactivate a program',
        operationId: 'reactivateProgram',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'program', description: 'Identifier of the program.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1)],
        responses: [
            new OA\Response(response: 200, description: 'The reactivated program.', content: new OA\JsonContent(ref: '#/components/schemas/ProgramResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Program not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function reactivate(Program $program): ProgramResource
    {
        $this->authorize('archive', $program);

        $this->programs->reactivate($program);

        return new ProgramResource($program->refresh());
    }

    #[OA\Delete(
        path: '/programs/{program}',
        summary: 'Delete a program',
        description: 'Hard delete. Refused with 409 while students or curriculum courses still reference the program; archive it instead.',
        operationId: 'deleteProgram',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'program', description: 'Identifier of the program.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1)],
        responses: [
            new OA\Response(response: 204, description: 'Program deleted.'),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Program not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Students or curriculum courses still reference the program.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Program $program): JsonResponse
    {
        $this->authorize('delete', $program);

        $this->programs->delete($program);

        return response()->json(null, 204);
    }
}
