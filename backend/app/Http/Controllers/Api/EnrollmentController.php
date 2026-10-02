<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Enrollment endpoints (module 9.9). Staff list all; managers enroll anyone;
 * a student enrolls/drops only themself and reads only their own.
 */
class EnrollmentController extends Controller
{
    private const DETAIL = ['student:id,student_number,first_name,last_name', 'section.offering.course:id,code,name,credits', 'semester.academicYear:id,code'];

    public function __construct(
        private readonly EnrollmentService $enrollments,
    ) {}

    #[OA\Get(
        path: '/enrollments',
        summary: 'List enrollments',
        operationId: 'listEnrollments',
        tags: ['Academics'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'search', description: 'Student ID or name.', schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'filters[student_id]', schema: new OA\Schema(type: 'integer', format: 'int64')),
            new OA\QueryParameter(name: 'filters[section_id]', schema: new OA\Schema(type: 'integer', format: 'int64')),
            new OA\QueryParameter(name: 'filters[semester_id]', schema: new OA\Schema(type: 'integer', format: 'int64')),
            new OA\QueryParameter(name: 'filters[status]', schema: new OA\Schema(type: 'string', enum: Enrollment::STATUSES)),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer', maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated enrollments.', content: new OA\JsonContent(ref: '#/components/schemas/EnrollmentCollection')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Only staff may list all enrollments.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Enrollment::class);

        return EnrollmentResource::collection($this->enrollments->paginate([
            ...self::filters($request),
            'per_page' => min(max($request->integer('per_page') ?: 15, 1), 100),
        ]));
    }

    /**
     * List filters from `search` and `filters[...]` (shared with the CSV export).
     *
     * @return array{search: mixed, student_id: ?int, section_id: ?int, semester_id: ?int, status: ?string}
     */
    public static function filters(Request $request): array
    {
        $filters = is_array($request->query('filters')) ? $request->query('filters') : [];

        return [
            'search' => is_string($request->query('search')) ? $request->query('search') : null,
            'student_id' => isset($filters['student_id']) ? (int) $filters['student_id'] : null,
            'section_id' => isset($filters['section_id']) ? (int) $filters['section_id'] : null,
            'semester_id' => isset($filters['semester_id']) ? (int) $filters['semester_id'] : null,
            'status' => in_array($filters['status'] ?? null, Enrollment::STATUSES, true) ? $filters['status'] : null,
        ];
    }

    #[OA\Post(
        path: '/enrollments',
        summary: 'Enroll a student in a section',
        description: 'Staff send `student_id`; a student enrolls themself (sending `student_id` is rejected). Checks: active student, registration open, not already in the offering, strict prerequisites passed, semester credit limit, seats.',
        operationId: 'createEnrollment',
        tags: ['Academics'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreEnrollmentRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Enrolled (status confirmed).', content: new OA\JsonContent(ref: '#/components/schemas/EnrollmentResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not allowed to enroll.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Student not active, registration closed, or section/offering full.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Already enrolled, prerequisites not met, credit limit exceeded, or invalid input.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function store(StoreEnrollmentRequest $request): JsonResponse
    {
        $this->authorize('create', Enrollment::class);

        $student = $request->user()->isRole(Role::Student->value)
            ? $request->user()->student
            : Student::query()->findOrFail($request->validated('student_id'));

        $enrollment = $this->enrollments->enroll($student, Section::query()->findOrFail($request->validated('section_id')));

        return (new EnrollmentResource($enrollment->load(self::DETAIL)))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/enrollments/{enrollment}',
        summary: 'Fetch an enrollment',
        operationId: 'getEnrollment',
        tags: ['Academics'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'enrollment', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'The enrollment.', content: new OA\JsonContent(ref: '#/components/schemas/EnrollmentResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not staff and not the student.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Enrollment $enrollment): EnrollmentResource
    {
        $this->authorize('view', $enrollment);

        return new EnrollmentResource($enrollment->load(self::DETAIL));
    }

    #[OA\Delete(
        path: '/enrollments/{enrollment}',
        summary: 'Drop an enrollment',
        description: 'Keeps the record: status becomes `dropped`, or `withdrawn` once attendance or a grade exists. Only pending/confirmed enrollments (409 otherwise).',
        operationId: 'dropEnrollment',
        tags: ['Academics'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'enrollment', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'The dropped/withdrawn enrollment.', content: new OA\JsonContent(ref: '#/components/schemas/EnrollmentResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a manager and not the student.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Not droppable.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Enrollment $enrollment): EnrollmentResource
    {
        $this->authorize('drop', $enrollment);

        return new EnrollmentResource($this->enrollments->drop($enrollment)->load(self::DETAIL));
    }

    #[OA\Post(
        path: '/enrollments/{enrollment}/complete',
        summary: 'Mark an enrollment completed',
        description: 'Managers only. A completed enrollment counts as passed for prerequisites unless a grade marks it F / 0 points.',
        operationId: 'completeEnrollment',
        tags: ['Academics'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'enrollment', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'The completed enrollment.', content: new OA\JsonContent(ref: '#/components/schemas/EnrollmentResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Only confirmed enrollments can be completed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function complete(Enrollment $enrollment): EnrollmentResource
    {
        $this->authorize('complete', $enrollment);

        return new EnrollmentResource($this->enrollments->complete($enrollment)->load(self::DETAIL));
    }

    #[OA\Get(
        path: '/students/{student}/enrollments',
        summary: "A student's enrollments",
        description: 'Staff may read any student; a student only their own.',
        operationId: 'getStudentEnrollments',
        tags: ['Academics'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'student', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'All enrollments of the student, newest first.', content: new OA\JsonContent(ref: '#/components/schemas/EnrollmentCollection')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not staff and not the student.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function forStudent(Student $student): AnonymousResourceCollection
    {
        $this->authorize('view', $student);

        return EnrollmentResource::collection(
            $student->enrollments()->with(self::DETAIL)->orderByDesc('enrolled_at')->get()
        );
    }
}
