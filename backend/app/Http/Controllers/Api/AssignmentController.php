<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignmentRequest;
use App\Http\Requests\GradeSubmissionRequest;
use App\Http\Requests\SubmitAssignmentRequest;
use App\Http\Resources\AssignmentResource;
use App\Http\Resources\SubmissionResource;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Enrollment;
use App\Models\Section;
use App\Services\AssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Assignment endpoints (module 9.12).
 */
class AssignmentController extends Controller
{
    public function __construct(
        private readonly AssignmentService $assignments,
    ) {}

    #[OA\Get(
        path: '/sections/{section}/assignments',
        summary: "A section's assignments",
        description: 'Staff and the section lecturers see all (with submission counts); enrolled students see published ones with their own submission.',
        operationId: 'listSectionAssignments',
        tags: ['Assignments'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'section', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Assignments ordered by due date.', content: new OA\JsonContent(ref: '#/components/schemas/AssignmentCollection')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not staff, a lecturer of the section, or enrolled.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request, Section $section): AnonymousResourceCollection
    {
        $this->authorize('viewSection', [Assignment::class, $section]);

        return AssignmentResource::collection($this->listFor($request, $section));
    }

    #[OA\Post(
        path: '/sections/{section}/assignments',
        summary: 'Create an assignment (unpublished)',
        description: 'Due date must be in the future and within the semester; completed semesters are frozen (409).',
        operationId: 'createAssignment',
        tags: ['Assignments'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'section', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AssignmentRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Created.', content: new OA\JsonContent(ref: '#/components/schemas/AssignmentResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a lecturer of the section or a manager.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Semester completed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function store(AssignmentRequest $request, Section $section): JsonResponse
    {
        $this->authorize('create', [Assignment::class, $section]);

        return (new AssignmentResource($this->assignments->create($section, $request->validated())))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/assignments/{assignment}',
        summary: 'Fetch an assignment',
        description: 'Students get their own submission in `my_submission`.',
        operationId: 'getAssignment',
        tags: ['Assignments'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'assignment', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'The assignment.', content: new OA\JsonContent(ref: '#/components/schemas/AssignmentResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not allowed (students: unpublished or not enrolled).', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Request $request, Assignment $assignment): AssignmentResource
    {
        $this->authorize('view', $assignment);

        return new AssignmentResource($this->withMine($request, $assignment->loadCount('submissions')));
    }

    #[OA\Put(
        path: '/assignments/{assignment}',
        summary: 'Update an assignment',
        description: 'A changed due date must be in the future; max score cannot drop below an awarded score.',
        operationId: 'updateAssignment',
        tags: ['Assignments'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'assignment', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AssignmentRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Updated.', content: new OA\JsonContent(ref: '#/components/schemas/AssignmentResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a lecturer of the section or a manager.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function update(AssignmentRequest $request, Assignment $assignment): AssignmentResource
    {
        $this->authorize('update', $assignment);

        return new AssignmentResource($this->assignments->update($assignment, $request->validated()));
    }

    #[OA\Delete(
        path: '/assignments/{assignment}',
        summary: 'Delete an assignment',
        description: 'Refused with 409 once submissions exist.',
        operationId: 'deleteAssignment',
        tags: ['Assignments'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'assignment', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 204, description: 'Deleted.'),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not allowed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Has submissions.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Assignment $assignment): JsonResponse
    {
        $this->authorize('delete', $assignment);

        $this->assignments->delete($assignment);

        return response()->json(null, 204);
    }

    #[OA\Post(
        path: '/assignments/{assignment}/publish',
        summary: 'Publish or unpublish',
        description: 'Send `published: false` to unpublish; refused (409) once students have submitted.',
        operationId: 'publishAssignment',
        tags: ['Assignments'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'assignment', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [new OA\Property(property: 'published', type: 'boolean', default: true)])),
        responses: [
            new OA\Response(response: 200, description: 'Updated.', content: new OA\JsonContent(ref: '#/components/schemas/AssignmentResourceResponse')),
            new OA\Response(response: 403, description: 'Not allowed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Cannot unpublish with submissions.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function publish(Request $request, Assignment $assignment): AssignmentResource
    {
        $this->authorize('update', $assignment);

        return new AssignmentResource($this->assignments->publish($assignment, $request->boolean('published', true)));
    }

    #[OA\Post(
        path: '/assignments/{assignment}/submissions',
        summary: 'Submit (or replace) my work',
        description: 'Students only, multipart `file` (pdf, docx, zip, png, jpg; max 10 MB). One submission per student; replacing is allowed until graded; after the due date the submission is flagged `late`.',
        operationId: 'submitAssignment',
        tags: ['Assignments'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'assignment', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(required: ['file'], properties: [new OA\Property(property: 'file', type: 'string', format: 'binary')]))),
        responses: [
            new OA\Response(response: 201, description: 'Stored.', content: new OA\JsonContent(ref: '#/components/schemas/SubmissionResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not an enrolled student or not published.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Already graded, or semester completed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid file.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function submit(SubmitAssignmentRequest $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('submit', $assignment);

        $submission = $this->assignments->submit($assignment, $request->user()->student, $request->file('file'), $request->user());

        return (new SubmissionResource($submission->load('file')))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/assignments/{assignment}/submissions',
        summary: 'All submissions of an assignment',
        operationId: 'listSubmissions',
        tags: ['Assignments'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'assignment', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Submissions with student and file metadata.', content: new OA\JsonContent(ref: '#/components/schemas/SubmissionCollection')),
            new OA\Response(response: 403, description: 'Not staff or a lecturer of the section.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function submissions(Assignment $assignment): AnonymousResourceCollection
    {
        $this->authorize('viewSubmissions', $assignment);

        return SubmissionResource::collection($assignment->submissions()->with(['enrollment.student', 'file'])->orderBy('submitted_at')->get());
    }

    #[OA\Post(
        path: '/submissions/{submission}/grade',
        summary: 'Grade a submission',
        description: 'Score between 0 and the assignment maximum, optional feedback; locks the submission.',
        operationId: 'gradeSubmission',
        tags: ['Assignments'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'submission', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/GradeSubmissionRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Graded.', content: new OA\JsonContent(ref: '#/components/schemas/SubmissionResourceResponse')),
            new OA\Response(response: 403, description: 'Not a lecturer of the section or a manager.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Score out of range.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function grade(GradeSubmissionRequest $request, AssignmentSubmission $submission): SubmissionResource
    {
        $this->authorize('grade', $submission->assignment);

        $data = $request->validated();

        return new SubmissionResource($this->assignments->grade($submission, (float) $data['score'], $data['feedback'] ?? null, $request->user())->load(['enrollment.student', 'file']));
    }

    #[OA\Get(
        path: '/submissions/{submission}/file',
        summary: 'Download a submitted file',
        description: 'Streams the private object; the owning student, the section lecturers and staff only.',
        operationId: 'downloadSubmission',
        tags: ['Assignments'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'submission', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'The file (attachment).'),
            new OA\Response(response: 403, description: 'Not allowed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'No file.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function download(AssignmentSubmission $submission): StreamedResponse
    {
        $this->authorize('download', $submission);

        return $this->assignments->download($submission);
    }

    /**
     * @return Collection<int, Assignment>
     */
    public function listFor(Request $request, Section $section): Collection
    {
        $isStudent = $request->user()->student !== null && ! $request->user()->can('viewSubmissions', new Assignment(['section_id' => $section->id]));

        return $section->assignments()
            ->when($isStudent, fn ($q) => $q->where('is_published', true))
            ->withCount('submissions')
            ->withCount(['submissions as graded_count' => fn ($q) => $q->where('status', 'graded')])
            ->get()
            ->map(fn (Assignment $assignment) => $this->withMine($request, $assignment));
    }

    private function withMine(Request $request, Assignment $assignment): Assignment
    {
        $student = $request->user()->student;

        if ($student !== null) {
            $enrollmentIds = Enrollment::query()->where('student_id', $student->getKey())->where('section_id', $assignment->section_id)->pluck('id');
            $assignment->setRelation('mySubmission', $assignment->submissions()->whereIn('enrollment_id', $enrollmentIds)->first());
        }

        return $assignment;
    }
}
