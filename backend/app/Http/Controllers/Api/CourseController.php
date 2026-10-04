<?php

namespace App\Http\Controllers\Api;

use App\Dto\UniversityStructure\CourseListFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCoursePrerequisiteRequest;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Services\CourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Course endpoints (module 9.7).
 *
 * Reads: Super Admin, University Admin, Department Admin. Writes: Super Admin and
 * University Admin — see `CoursePolicy`. Every operation documents 401 and 403.
 */
class CourseController extends Controller
{
    public function __construct(
        private readonly CourseService $courses,
    ) {}

    #[OA\Get(
        path: '/courses',
        summary: 'List courses',
        description: 'Paginated courses with prerequisite and program counts. Supports search over code and name, filtering by department, program, status and level, whitelisted sorting and a capped page size.',
        operationId: 'listCourses',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'search', description: 'Partial match against `code` or `name`.', schema: new OA\Schema(type: 'string'), example: 'Data'),
            new OA\QueryParameter(name: 'filters[department_id]', description: 'Courses of this department.', schema: new OA\Schema(type: 'integer', format: 'int64')),
            new OA\QueryParameter(name: 'filters[program_id]', description: 'Courses in this program curriculum.', schema: new OA\Schema(type: 'integer', format: 'int64')),
            new OA\QueryParameter(name: 'filters[status]', description: 'Lifecycle status.', schema: new OA\Schema(type: 'string', enum: Course::STATUSES)),
            new OA\QueryParameter(name: 'filters[course_level]', description: 'Course level.', schema: new OA\Schema(type: 'string', enum: Course::LEVELS)),
            new OA\QueryParameter(name: 'sort_by', description: 'Whitelisted sort column.', schema: new OA\Schema(type: 'string', enum: CourseListFilters::SORTABLE, default: 'code')),
            new OA\QueryParameter(name: 'sort_dir', description: 'Sort direction.', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'asc')),
            new OA\QueryParameter(name: 'per_page', description: 'Records per page (default 15, max 100).', schema: new OA\Schema(type: 'integer', format: 'int32', default: 15, maximum: 100)),
            new OA\QueryParameter(name: 'page', description: 'Page number.', schema: new OA\Schema(type: 'integer', format: 'int32', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated collection of courses.', content: new OA\JsonContent(ref: '#/components/schemas/CourseCollection')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Role may not view courses.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Course::class);

        return CourseResource::collection(
            $this->courses->paginate(CourseListFilters::fromInput($request->query()), $request->user())
        );
    }

    #[OA\Post(
        path: '/courses',
        summary: 'Create a course',
        description: '`code` is globally unique and `credits` must be positive. `status` may be `draft` or `active` (default `active`); archiving has its own endpoint.',
        operationId: 'createCourse',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreCourseRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Course created.', content: new OA\JsonContent(ref: '#/components/schemas/CourseResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function store(StoreCourseRequest $request): JsonResponse
    {
        $this->authorize('create', Course::class);

        $course = $this->courses->create($request->validated());

        return (new CourseResource($course->load('department:id,code,name')))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/courses/{course}',
        summary: 'Fetch a course',
        description: 'Includes its prerequisites and the programs whose curriculum contains it.',
        operationId: 'getCourse',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'course', description: 'Identifier of the course.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1)],
        responses: [
            new OA\Response(response: 200, description: 'The requested course.', content: new OA\JsonContent(ref: '#/components/schemas/CourseResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Role may not view courses.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Course not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Course $course): CourseResource
    {
        $this->authorize('view', $course);

        return new CourseResource($course->load(['department:id,code,name', 'prerequisites', 'programs']));
    }

    #[OA\Put(
        path: '/courses/{course}',
        summary: 'Update a course',
        description: 'An archived course keeps its status; use the reactivate endpoint to bring it back.',
        operationId: 'updateCourse',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'course', description: 'Identifier of the course.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1)],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateCourseRequest')),
        responses: [
            new OA\Response(response: 200, description: 'The updated course.', content: new OA\JsonContent(ref: '#/components/schemas/CourseResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Course not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    #[OA\Patch(
        path: '/courses/{course}',
        summary: 'Update a course (PATCH)',
        description: 'Same validation as PUT: `code`, `name` and `credits` remain required.',
        operationId: 'patchCourse',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'course', description: 'Identifier of the course.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1)],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateCourseRequest')),
        responses: [
            new OA\Response(response: 200, description: 'The updated course.', content: new OA\JsonContent(ref: '#/components/schemas/CourseResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Course not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function update(UpdateCourseRequest $request, Course $course): CourseResource
    {
        $this->authorize('update', $course);

        $this->courses->update($course, $request->validated());

        return new CourseResource($course->refresh()->load('department:id,code,name'));
    }

    #[OA\Post(
        path: '/courses/{course}/archive',
        summary: 'Archive a course',
        description: 'Sets the status to `archived`. Archived courses cannot be added to a curriculum or used as a prerequisite.',
        operationId: 'archiveCourse',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'course', description: 'Identifier of the course.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1)],
        responses: [
            new OA\Response(response: 200, description: 'The archived course.', content: new OA\JsonContent(ref: '#/components/schemas/CourseResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Course not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function archive(Course $course): CourseResource
    {
        $this->authorize('archive', $course);

        $this->courses->archive($course);

        return new CourseResource($course->refresh());
    }

    #[OA\Post(
        path: '/courses/{course}/reactivate',
        summary: 'Reactivate a course',
        description: 'Sets the status back to `active`.',
        operationId: 'reactivateCourse',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'course', description: 'Identifier of the course.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1)],
        responses: [
            new OA\Response(response: 200, description: 'The reactivated course.', content: new OA\JsonContent(ref: '#/components/schemas/CourseResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Course not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function reactivate(Course $course): CourseResource
    {
        $this->authorize('archive', $course);

        $this->courses->reactivate($course);

        return new CourseResource($course->refresh());
    }

    #[OA\Delete(
        path: '/courses/{course}',
        summary: 'Delete a course',
        description: 'Hard delete. Refused with 409 while a program curriculum, another course (as prerequisite) or a course offering references it; archive it instead.',
        operationId: 'deleteCourse',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'course', description: 'Identifier of the course.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1)],
        responses: [
            new OA\Response(response: 204, description: 'Course deleted.'),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Course not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'The course is still referenced.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Course $course): JsonResponse
    {
        $this->authorize('delete', $course);

        $this->courses->delete($course);

        return response()->json(null, 204);
    }

    #[OA\Post(
        path: '/courses/{course}/prerequisites',
        summary: 'Add a prerequisite',
        description: 'Rejected with 422 when the prerequisite is the course itself, archived, already set, or would create a prerequisite cycle.',
        operationId: 'addCoursePrerequisite',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'course', description: 'Identifier of the course.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1)],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreCoursePrerequisiteRequest')),
        responses: [
            new OA\Response(response: 201, description: 'The course with its updated prerequisites.', content: new OA\JsonContent(ref: '#/components/schemas/CourseResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Course not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed or the prerequisite is not allowed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function addPrerequisite(StoreCoursePrerequisiteRequest $request, Course $course): JsonResponse
    {
        $this->authorize('update', $course);

        $this->courses->addPrerequisite(
            $course,
            Course::query()->findOrFail($request->validated('prerequisite_course_id')),
            (bool) $request->validated('is_strict', true),
        );

        return (new CourseResource($course->load(['department:id,code,name', 'prerequisites'])))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Delete(
        path: '/courses/{course}/prerequisites/{prerequisite}',
        summary: 'Remove a prerequisite',
        operationId: 'removeCoursePrerequisite',
        tags: ['University Structure'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'course', description: 'Identifier of the course.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1),
            new OA\PathParameter(name: 'prerequisite', description: 'Identifier of the prerequisite course.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 2),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Prerequisite removed.'),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Course not found, or it is not a prerequisite of this course.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function removePrerequisite(Course $course, Course $prerequisite): JsonResponse
    {
        $this->authorize('update', $course);
        abort_unless(
            $course->prerequisites()->whereKey($prerequisite->getKey())->exists(),
            404,
            'That course is not a prerequisite.',
        );

        $this->courses->removePrerequisite($course, $prerequisite);

        return response()->json(null, 204);
    }
}
