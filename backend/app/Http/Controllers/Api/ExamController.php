<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CorrectExamResultRequest;
use App\Http\Requests\ExamRequest;
use App\Http\Requests\RecordExamResultsRequest;
use App\Http\Resources\ExamResource;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Section;
use App\Models\Student;
use App\Services\ExamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Examination endpoints (module 9.13).
 */
class ExamController extends Controller
{
    public function __construct(
        private readonly ExamService $exams,
    ) {}

    #[OA\Get(
        path: '/sections/{section}/exams',
        summary: "A section's exams",
        description: 'Ordered by date. Staff and the section lecturers also get `results_count`; enrolled students see the schedule.',
        operationId: 'listSectionExams',
        tags: ['Examinations'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'section', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Exams.', content: new OA\JsonContent(ref: '#/components/schemas/ExamCollection')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not staff, a lecturer of the section, or enrolled.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request, Section $section): AnonymousResourceCollection
    {
        $this->authorize('viewSection', [Exam::class, $section]);

        return ExamResource::collection($this->listFor($request, $section));
    }

    #[OA\Post(
        path: '/sections/{section}/exams',
        summary: 'Create an exam',
        description: 'Date within the semester; section exam weights total at most 100; a timed exam may not overlap another exam of the section or of a section sharing a student (409). Results start unreleased.',
        operationId: 'createExam',
        tags: ['Examinations'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'section', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ExamRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Created.', content: new OA\JsonContent(ref: '#/components/schemas/ExamResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a lecturer of the section or a manager.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Time clash, or semester completed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function store(ExamRequest $request, Section $section): JsonResponse
    {
        $this->authorize('create', [Exam::class, $section]);

        return (new ExamResource($this->exams->create($section, $request->validated())))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/exams/{exam}',
        summary: 'Fetch an exam with results',
        description: 'Staff and the section lecturers get the full roster in `results`; an enrolled student gets `my_result` once results are released.',
        operationId: 'getExam',
        tags: ['Examinations'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'exam', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'The exam.', content: new OA\JsonContent(ref: '#/components/schemas/ExamResultsResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not allowed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Request $request, Exam $exam): JsonResponse
    {
        $this->authorize('view', $exam);

        $payload = ['data' => (new ExamResource($exam->loadCount('results')))->resolve($request)];

        if ($request->user()->can('viewResults', $exam)) {
            $payload['results'] = $this->exams->roster($exam);
        } elseif ($student = $request->user()->student) {
            $payload['my_result'] = $this->exams->forStudent($student)->firstWhere('id', $exam->id)['my_result'] ?? null;
        }

        return response()->json($payload);
    }

    #[OA\Put(
        path: '/exams/{exam}',
        summary: 'Update an exam',
        description: 'Same rules as create; max score cannot drop below a recorded score.',
        operationId: 'updateExam',
        tags: ['Examinations'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'exam', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ExamRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Updated.', content: new OA\JsonContent(ref: '#/components/schemas/ExamResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a lecturer of the section or a manager.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Time clash, or semester completed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function update(ExamRequest $request, Exam $exam): ExamResource
    {
        $this->authorize('update', $exam);

        return new ExamResource($this->exams->update($exam, $request->validated()));
    }

    #[OA\Delete(
        path: '/exams/{exam}',
        summary: 'Delete an exam',
        description: 'Refused with 409 once results exist.',
        operationId: 'deleteExam',
        tags: ['Examinations'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'exam', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 204, description: 'Deleted.'),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not allowed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Has results.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Exam $exam): JsonResponse
    {
        $this->authorize('delete', $exam);

        $this->exams->delete($exam);

        return response()->json(null, 204);
    }

    #[OA\Post(
        path: '/exams/{exam}/publish',
        summary: 'Release or withhold results',
        description: 'Send `published: false` to withhold the results from students again.',
        operationId: 'publishExamResults',
        tags: ['Examinations'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'exam', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [new OA\Property(property: 'published', type: 'boolean', default: true)])),
        responses: [
            new OA\Response(response: 200, description: 'Updated.', content: new OA\JsonContent(ref: '#/components/schemas/ExamResourceResponse')),
            new OA\Response(response: 403, description: 'Not allowed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function publish(Request $request, Exam $exam): ExamResource
    {
        $this->authorize('update', $exam);

        return new ExamResource($this->exams->publish($exam, $request->boolean('published', true)));
    }

    #[OA\Post(
        path: '/exams/{exam}/results',
        summary: 'Record results (bulk)',
        description: 'Upserts one result per enrollment; only students of the section; score within [0, max]; not before the exam date; completed semester frozen (409).',
        operationId: 'recordExamResults',
        tags: ['Examinations'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'exam', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/RecordExamResultsRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Saved; returns the roster.', content: new OA\JsonContent(ref: '#/components/schemas/ExamResultsResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a lecturer of the section or a manager.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Semester completed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function record(RecordExamResultsRequest $request, Exam $exam): JsonResponse
    {
        $this->authorize('update', $exam);

        $this->exams->record($exam, $request->validated('results'), $request->user());

        return response()->json(['data' => (new ExamResource($exam->loadCount('results')))->resolve($request), 'results' => $this->exams->roster($exam)]);
    }

    #[OA\Patch(
        path: '/exam-results/{result}',
        summary: 'Correct one result',
        operationId: 'correctExamResult',
        tags: ['Examinations'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'result', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CorrectExamResultRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Corrected.', content: new OA\JsonContent(ref: '#/components/schemas/ExamResultsResponse')),
            new OA\Response(response: 403, description: 'Not allowed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Semester completed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function correct(CorrectExamResultRequest $request, ExamResult $result): JsonResponse
    {
        $exam = $result->exam;
        $this->authorize('update', $exam);

        $this->exams->correct($result, $request->validated('score'), $request->validated('remarks'), $request->user());

        return response()->json(['data' => (new ExamResource($exam->loadCount('results')))->resolve($request), 'results' => $this->exams->roster($exam)]);
    }

    #[OA\Get(
        path: '/students/{student}/exams',
        summary: "A student's exams",
        description: 'Schedule across the student\'s sections with `my_result` once released. Staff may read any student; a student only their own.',
        operationId: 'getStudentExams',
        tags: ['Examinations'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'student', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Exams.', content: new OA\JsonContent(ref: '#/components/schemas/StudentExamsResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not allowed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function student(Student $student): JsonResponse
    {
        $this->authorize('viewStudent', [Exam::class, $student]);

        return response()->json(['data' => $this->exams->forStudent($student)]);
    }

    /**
     * Shared with the web controller.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Exam>
     */
    public function listFor(Request $request, Section $section)
    {
        $query = $section->exams();

        if ($request->user()->can('viewResults', new Exam(['section_id' => $section->id]))) {
            $query->withCount('results');
        }

        return $query->get();
    }
}
