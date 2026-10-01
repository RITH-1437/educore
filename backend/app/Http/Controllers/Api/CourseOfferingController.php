<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseOfferingRequest;
use App\Http\Requests\StoreSectionRequest;
use App\Http\Requests\UpdateCourseOfferingRequest;
use App\Http\Resources\CourseOfferingResource;
use App\Http\Resources\SectionResource;
use App\Models\CourseOffering;
use App\Services\CourseOfferingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Course offering endpoints (module 9.8). Reads: Super Admin, University Admin,
 * Faculty Admin. Writes: Super Admin, University Admin.
 */
class CourseOfferingController extends Controller
{
    private const DETAIL = ['course:id,code,name,credits', 'semester.academicYear:id,code,name', 'sections.lecturers'];

    public function __construct(
        private readonly CourseOfferingService $offerings,
    ) {}

    #[OA\Get(
        path: '/offerings',
        summary: 'List course offerings',
        operationId: 'listOfferings',
        tags: ['Academics'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'search', description: 'Course code or name.', schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'filters[semester_id]', schema: new OA\Schema(type: 'integer', format: 'int64')),
            new OA\QueryParameter(name: 'filters[academic_year_id]', schema: new OA\Schema(type: 'integer', format: 'int64')),
            new OA\QueryParameter(name: 'filters[course_id]', schema: new OA\Schema(type: 'integer', format: 'int64')),
            new OA\QueryParameter(name: 'filters[status]', schema: new OA\Schema(type: 'string', enum: CourseOffering::STATUSES)),
            new OA\QueryParameter(name: 'per_page', description: 'Default 15, max 100.', schema: new OA\Schema(type: 'integer', maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated offerings with section count and total capacity.', content: new OA\JsonContent(ref: '#/components/schemas/CourseOfferingCollection')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Role may not view offerings.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CourseOffering::class);

        $filters = is_array($request->query('filters')) ? $request->query('filters') : [];
        $status = $filters['status'] ?? null;

        return CourseOfferingResource::collection($this->offerings->paginate([
            'search' => $request->query('search'),
            'semester_id' => isset($filters['semester_id']) ? (int) $filters['semester_id'] : null,
            'academic_year_id' => isset($filters['academic_year_id']) ? (int) $filters['academic_year_id'] : null,
            'course_id' => isset($filters['course_id']) ? (int) $filters['course_id'] : null,
            'status' => in_array($status, CourseOffering::STATUSES, true) ? $status : null,
            'per_page' => min(max($request->integer('per_page') ?: 15, 1), 100),
        ]));
    }

    #[OA\Post(
        path: '/offerings',
        summary: 'Create a course offering',
        description: 'One per course per semester. Only active courses; never in a completed semester (409).',
        operationId: 'createOffering',
        tags: ['Academics'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreCourseOfferingRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Offering created.', content: new OA\JsonContent(ref: '#/components/schemas/CourseOfferingResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Semester completed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed (duplicate offering, inactive course).', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function store(StoreCourseOfferingRequest $request): JsonResponse
    {
        $this->authorize('create', CourseOffering::class);

        $offering = $this->offerings->create($request->validated());

        return (new CourseOfferingResource($offering->load(self::DETAIL)))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/offerings/{offering}',
        summary: 'Fetch an offering with its sections and lecturers',
        operationId: 'getOffering',
        tags: ['Academics'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'offering', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'The offering.', content: new OA\JsonContent(ref: '#/components/schemas/CourseOfferingResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Role may not view offerings.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(CourseOffering $offering): CourseOfferingResource
    {
        $this->authorize('view', $offering);

        return new CourseOfferingResource($offering->load(self::DETAIL));
    }

    #[OA\Put(
        path: '/offerings/{offering}',
        summary: 'Update an offering (status, max enrollments, notes)',
        description: 'Course and semester are the offering identity and cannot change.',
        operationId: 'updateOffering',
        tags: ['Academics'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'offering', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateCourseOfferingRequest')),
        responses: [
            new OA\Response(response: 200, description: 'The updated offering.', content: new OA\JsonContent(ref: '#/components/schemas/CourseOfferingResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function update(UpdateCourseOfferingRequest $request, CourseOffering $offering): CourseOfferingResource
    {
        $this->authorize('update', $offering);

        $this->offerings->update($offering, $request->validated());

        return new CourseOfferingResource($offering->refresh()->load(self::DETAIL));
    }

    #[OA\Delete(
        path: '/offerings/{offering}',
        summary: 'Delete an offering',
        description: 'Refused with 409 while it has sections.',
        operationId: 'deleteOffering',
        tags: ['Academics'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'offering', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 204, description: 'Deleted.'),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'The offering has sections.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(CourseOffering $offering): JsonResponse
    {
        $this->authorize('delete', $offering);

        $this->offerings->delete($offering);

        return response()->json(null, 204);
    }

    #[OA\Post(
        path: '/offerings/{offering}/sections',
        summary: 'Add a section to an offering',
        description: 'Code unique within the offering; capacity 1–1000. Refused (409) in a completed semester.',
        operationId: 'createSection',
        tags: ['Academics'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'offering', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreSectionRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Section created.', content: new OA\JsonContent(ref: '#/components/schemas/SectionResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Semester completed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function storeSection(StoreSectionRequest $request, CourseOffering $offering): JsonResponse
    {
        $this->authorize('update', $offering);

        $section = $this->offerings->createSection($offering, $request->validated());

        return (new SectionResource($section->load('lecturers')))->response()->setStatusCode(201);
    }
}
