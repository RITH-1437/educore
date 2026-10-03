<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InternshipCompanyRequest;
use App\Http\Requests\InternshipReportRequest;
use App\Http\Requests\InternshipRequest;
use App\Http\Resources\InternshipResource;
use App\Models\Internship;
use App\Models\InternshipCompany;
use App\Models\InternshipEvaluation;
use App\Models\InternshipReport;
use App\Services\InternshipService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Internship management (module 9.22).
 */
class InternshipController extends Controller
{
    public const DETAIL = ['company', 'student', 'reports.file', 'evaluations'];

    public function __construct(
        private readonly InternshipService $internships,
    ) {}

    // -------------------------------------------------------------- companies

    #[OA\Get(
        path: '/internship-companies',
        summary: 'Host companies',
        description: 'Students see active companies (for the application form); staff see all.',
        operationId: 'listInternshipCompanies',
        tags: ['Internships'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Companies by name.', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/InternshipCompany'))])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not staff or a student.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function companies(Request $request): JsonResponse
    {
        $this->authorize('viewCompanies', Internship::class);

        return response()->json(['data' => self::companyList(! $request->user()->can('viewAny', Internship::class))]);
    }

    #[OA\Post(
        path: '/internship-companies',
        summary: 'Add a host company',
        operationId: 'createInternshipCompany',
        tags: ['Internships'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/InternshipCompanyRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Created.', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/InternshipCompany')])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed (name unique).', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function storeCompany(InternshipCompanyRequest $request): JsonResponse
    {
        $this->authorize('manageCompanies', Internship::class);

        return response()->json(['data' => $this->internships->saveCompany(null, $request->validated())], 201);
    }

    #[OA\Put(
        path: '/internship-companies/{company}',
        summary: 'Update a host company',
        description: 'Set `is_active` false to retire it; past internships keep it.',
        operationId: 'updateInternshipCompany',
        tags: ['Internships'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'company', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/InternshipCompanyRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Updated.', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/InternshipCompany')])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    #[OA\Patch(
        path: '/internship-companies/{company}',
        summary: 'Update a host company (PATCH)',
        description: 'Same full-update validation as PUT.',
        operationId: 'patchInternshipCompany',
        tags: ['Internships'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'company', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/InternshipCompanyRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Updated.', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/InternshipCompany')])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function updateCompany(InternshipCompanyRequest $request, InternshipCompany $company): JsonResponse
    {
        $this->authorize('manageCompanies', Internship::class);

        return response()->json(['data' => $this->internships->saveCompany($company, $request->validated())]);
    }

    // ------------------------------------------------------------ internships

    #[OA\Get(
        path: '/internships',
        summary: 'List internships',
        description: 'Staff see all (`filters[status]`); a student sees their own.',
        operationId: 'listInternships',
        tags: ['Internships'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'filters[status]', required: false, schema: new OA\Schema(type: 'string', enum: ['draft', 'submitted', 'under_review', 'approved', 'rejected', 'in_progress', 'completed', 'cancelled'])),
            new OA\QueryParameter(name: 'page', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Internships.', content: new OA\JsonContent(ref: '#/components/schemas/InternshipCollection')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not staff or a student.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        return InternshipResource::collection($this->listFor($request));
    }

    #[OA\Post(
        path: '/internships',
        summary: 'Start an internship application (student)',
        description: 'Creates a draft for the signed-in student. One open application (draft or active) at a time (409).',
        operationId: 'applyInternship',
        tags: ['Internships'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/InternshipRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Draft created.', content: new OA\JsonContent(ref: '#/components/schemas/InternshipResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a student with a profile.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'An open application exists.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed (inactive company, dates).', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function store(InternshipRequest $request): JsonResponse
    {
        $this->authorize('apply', Internship::class);

        return (new InternshipResource($this->internships->apply($request->user()->student, $request->validated())->load(self::DETAIL)))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/internships/{internship}',
        summary: 'Fetch an internship with reports and evaluations',
        operationId: 'getInternship',
        tags: ['Internships'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'internship', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'The internship.', content: new OA\JsonContent(ref: '#/components/schemas/InternshipResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not staff or the student.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Internship $internship): InternshipResource
    {
        $this->authorize('view', $internship);

        return new InternshipResource($internship->load(self::DETAIL));
    }

    #[OA\Put(
        path: '/internships/{internship}',
        summary: 'Edit an internship',
        description: 'The student while it is a draft; managers until it is final (e.g. a company change after approval). 409 otherwise.',
        operationId: 'updateInternship',
        tags: ['Internships'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'internship', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/InternshipRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Updated.', content: new OA\JsonContent(ref: '#/components/schemas/InternshipResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not the student, a manager, or the student\'s Faculty Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Not editable in this status.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    #[OA\Patch(
        path: '/internships/{internship}',
        summary: 'Edit an internship (PATCH)',
        description: 'Same full-update validation as PUT.',
        operationId: 'patchInternship',
        tags: ['Internships'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'internship', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/InternshipRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Updated.', content: new OA\JsonContent(ref: '#/components/schemas/InternshipResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not the student, a manager, or the student\'s Faculty Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Not editable in this status.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function update(InternshipRequest $request, Internship $internship): InternshipResource
    {
        $manager = $request->user()->can('process', $internship);
        abort_unless($manager || $request->user()->can('act', $internship), 403);

        return new InternshipResource($this->internships->update($internship, $request->validated(), $manager)->load(self::DETAIL));
    }

    #[OA\Post(
        path: '/internships/{internship}/{action}',
        summary: 'Move an internship through its workflow',
        description: '`submit` (student, from draft) · `review` (manager, submitted → under review) · `approve` (manager, optional `note`) · `reject` (manager, `reason` required) · `start` (manager, approved → in progress) · `complete` (manager, needs a final report, optional `note`) · `cancel` (student before approval, or manager with `reason` — early termination). Illegal transitions return 409. Manager actions: Super Admin, University Admin, or a Faculty Admin for a student of their faculty.',
        operationId: 'transitionInternship',
        tags: ['Internships'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'internship', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
            new OA\PathParameter(name: 'action', required: true, schema: new OA\Schema(type: 'string', enum: ['submit', 'review', 'approve', 'reject', 'start', 'complete', 'cancel'])),
        ],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'note', type: 'string', maxLength: 1000, nullable: true),
            new OA\Property(property: 'reason', type: 'string', maxLength: 1000, nullable: true),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Moved.', content: new OA\JsonContent(ref: '#/components/schemas/InternshipResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not allowed for this action.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Illegal transition, an active internship exists, or no final report.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Reason missing.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function transition(Request $request, Internship $internship, string $action): InternshipResource
    {
        return new InternshipResource($this->runTransition($request, $internship, $action)->load(self::DETAIL));
    }

    // ------------------------------------------------------------ reports etc.

    #[OA\Post(
        path: '/internships/{internship}/reports',
        summary: 'Submit an internship report (student)',
        description: 'multipart/form-data. Initial / progress reports once approved; the final report once in progress. Optional PDF / DOCX stored privately.',
        operationId: 'submitInternshipReport',
        tags: ['Internships'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'internship', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(
            required: ['report_type', 'title'],
            properties: [
                new OA\Property(property: 'report_type', type: 'string', enum: ['initial', 'progress', 'final']),
                new OA\Property(property: 'title', type: 'string', maxLength: 255),
                new OA\Property(property: 'summary', type: 'string', nullable: true),
                new OA\Property(property: 'file', type: 'string', format: 'binary', nullable: true),
            ]
        ))),
        responses: [
            new OA\Response(response: 201, description: 'Submitted; the updated internship.', content: new OA\JsonContent(ref: '#/components/schemas/InternshipResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not the student.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Wrong internship status for this report type.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed (type, file type / size).', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function report(InternshipReportRequest $request, Internship $internship): JsonResponse
    {
        $this->authorize('act', $internship);

        $this->internships->addReport($internship, $request->safe()->except('file'), $request->file('file'), $request->user());

        return (new InternshipResource($internship->refresh()->load(self::DETAIL)))->response()->setStatusCode(201);
    }

    #[OA\Post(
        path: '/internship-reports/{report}/review',
        summary: 'Mark a report reviewed',
        operationId: 'reviewInternshipReport',
        tags: ['Internships'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'report', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(properties: [new OA\Property(property: 'reviewer_comment', type: 'string', maxLength: 2000, nullable: true)])),
        responses: [
            new OA\Response(response: 200, description: 'Reviewed; the updated internship.', content: new OA\JsonContent(ref: '#/components/schemas/InternshipResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a manager or the student\'s Faculty Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function reviewReport(Request $request, InternshipReport $report): InternshipResource
    {
        $this->authorize('process', $report->internship);
        $comment = $request->validate(['reviewer_comment' => ['nullable', 'string', 'max:2000']])['reviewer_comment'] ?? null;

        $this->internships->reviewReport($report, $comment);

        return new InternshipResource($report->internship->load(self::DETAIL));
    }

    #[OA\Get(
        path: '/internship-reports/{report}/file',
        summary: 'Download a report file',
        operationId: 'downloadInternshipReport',
        tags: ['Internships'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'report', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'The file.', content: new OA\MediaType(mediaType: 'application/octet-stream', schema: new OA\Schema(type: 'string', format: 'binary'))),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not staff or the student.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'No file.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function downloadReport(InternshipReport $report): StreamedResponse
    {
        $this->authorize('view', $report->internship);

        return $this->internships->downloadReport($report);
    }

    #[OA\Post(
        path: '/internships/{internship}/evaluations',
        summary: 'Record a supervisor or faculty evaluation',
        description: 'One per evaluator type (saving again replaces it); once the internship has started (409 before).',
        operationId: 'evaluateInternship',
        tags: ['Internships'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'internship', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/InternshipEvaluationRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Saved; the updated internship.', content: new OA\JsonContent(ref: '#/components/schemas/InternshipResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a manager or the student\'s Faculty Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Not started yet.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function evaluate(Request $request, Internship $internship): InternshipResource
    {
        $this->authorize('process', $internship);

        $this->internships->evaluate($internship, self::evaluationData($request), $request->user());

        return new InternshipResource($internship->refresh()->load(self::DETAIL));
    }

    // ---------------------------------------------------------------- shared

    /** Shared by the API and web controllers. */
    public function runTransition(Request $request, Internship $internship, string $action): Internship
    {
        $user = $request->user();
        $manager = $user->can('process', $internship);
        $text = fn (string $field) => $request->validate([$field => ['nullable', 'string', 'max:1000']])[$field] ?? null;

        // The student acts on their own application; everything else is a manager decision.
        match ($action) {
            'submit' => $this->authorize('act', $internship),
            'cancel' => $manager ?: $this->authorize('act', $internship),
            default => $this->authorize('process', $internship),
        };

        return match ($action) {
            'submit' => $this->internships->submit($internship),
            'review' => $this->internships->review($internship, $user),
            'approve' => $this->internships->approve($internship, $user, $text('note')),
            'reject' => $this->internships->reject($internship, $user, $request->validate(['reason' => ['required', 'string', 'max:1000']])['reason']),
            'start' => $this->internships->start($internship, $user),
            'complete' => $this->internships->complete($internship, $user, $text('note')),
            'cancel' => $this->internships->cancel($internship, $user, $manager, $text('reason')),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public static function evaluationData(Request $request): array
    {
        return $request->validate([
            'evaluator_type' => ['required', Rule::in(InternshipEvaluation::TYPES)],
            'evaluator_name' => ['nullable', 'string', 'max:150'],
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'rating' => ['nullable', Rule::in(InternshipEvaluation::RATINGS)],
            'comments' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    public function listFor(Request $request): LengthAwarePaginator
    {
        $user = $request->user();
        $status = $request->validate(['filters.status' => ['nullable', Rule::in(Internship::STATUSES)]])['filters']['status'] ?? null;
        $query = Internship::query()->with('company', 'student')->when($status, fn ($q) => $q->where('status', $status))
            ->orderByRaw("case status when 'submitted' then 0 when 'under_review' then 1 when 'approved' then 2 when 'in_progress' then 3 else 4 end")
            ->latest('updated_at');

        if (! $user->can('viewAny', Internship::class)) {
            abort_unless($user->can('apply', Internship::class), 403);
            $query->where('student_id', $user->student->getKey());
        } else {
            $query->visibleTo($user);
        }

        return $query->paginate(15)->withQueryString();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function companyList(bool $activeOnly): array
    {
        return InternshipCompany::query()->when($activeOnly, fn ($q) => $q->where('is_active', true))->withCount('internships')->orderBy('name')->get()
            ->map(fn (InternshipCompany $c) => [...$c->only(['id', 'name', 'industry', 'contact_name', 'contact_email', 'contact_phone', 'address', 'website', 'is_active']), 'internships_count' => $c->internships_count])
            ->values()->all();
    }
}
