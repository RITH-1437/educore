<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectDocumentRequestRequest;
use App\Http\Requests\StoreDocumentRequestRequest;
use App\Http\Resources\DocumentRequestResource;
use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Services\DocumentService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Document requests, generated documents and public verification
 * (modules 9.16 / 9.17).
 */
class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentService $documents,
    ) {}

    #[OA\Get(
        path: '/document-types',
        summary: 'Requestable document types',
        operationId: 'listDocumentTypes',
        tags: ['Documents'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Active types that have a template.', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/DocumentType'))])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function types(): JsonResponse
    {
        return response()->json(['data' => self::typeOptions()]);
    }

    #[OA\Get(
        path: '/document-requests',
        summary: 'List document requests',
        description: 'Staff see every request (`filters[status]`); a student sees only their own.',
        operationId: 'listDocumentRequests',
        tags: ['Documents'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'filters[status]', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'rejected', 'generated'])),
            new OA\QueryParameter(name: 'page', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Requests, newest first.', content: new OA\JsonContent(ref: '#/components/schemas/DocumentRequestCollection')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Neither staff nor a student.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid filter.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        return DocumentRequestResource::collection($this->listFor($request));
    }

    #[OA\Post(
        path: '/document-requests',
        summary: 'Request a document (student)',
        description: 'The student is the signed-in user. `semester_id` is required for an academic result. One open (pending / approved) request per type and semester (409).',
        operationId: 'createDocumentRequest',
        tags: ['Documents'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreDocumentRequestRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Created.', content: new OA\JsonContent(ref: '#/components/schemas/DocumentRequestResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a student with a profile.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'An open request already exists.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function store(StoreDocumentRequestRequest $request): JsonResponse
    {
        $this->authorize('create', DocumentRequest::class);

        $created = $this->documents->request($request->user()->student, $request->validated());

        return (new DocumentRequestResource($created->load(self::RELATIONS)))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/document-requests/{documentRequest}',
        summary: 'Fetch a document request',
        operationId: 'getDocumentRequest',
        tags: ['Documents'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'documentRequest', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'The request.', content: new OA\JsonContent(ref: '#/components/schemas/DocumentRequestResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not staff or the requesting student.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(DocumentRequest $documentRequest): DocumentRequestResource
    {
        $this->authorize('view', $documentRequest);

        return new DocumentRequestResource($documentRequest->load(self::RELATIONS));
    }

    #[OA\Post(
        path: '/document-requests/{documentRequest}/approve',
        summary: 'Approve a pending request',
        operationId: 'approveDocumentRequest',
        tags: ['Documents'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'documentRequest', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Approved.', content: new OA\JsonContent(ref: '#/components/schemas/DocumentRequestResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a manager, or a Department Admin outside the student\'s department.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Not pending.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function approve(Request $request, DocumentRequest $documentRequest): DocumentRequestResource
    {
        $this->authorize('process', $documentRequest);

        return new DocumentRequestResource($this->documents->approve($documentRequest, $request->user())->load(self::RELATIONS));
    }

    #[OA\Post(
        path: '/document-requests/{documentRequest}/reject',
        summary: 'Reject a pending request',
        description: 'A reason is required; the student may submit a new request.',
        operationId: 'rejectDocumentRequest',
        tags: ['Documents'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'documentRequest', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['rejection_reason'], properties: [new OA\Property(property: 'rejection_reason', type: 'string', maxLength: 500)])),
        responses: [
            new OA\Response(response: 200, description: 'Rejected.', content: new OA\JsonContent(ref: '#/components/schemas/DocumentRequestResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a manager, or a Department Admin outside the student\'s department.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Not pending.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Reason missing.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function reject(RejectDocumentRequestRequest $request, DocumentRequest $documentRequest): DocumentRequestResource
    {
        $this->authorize('process', $documentRequest);

        return new DocumentRequestResource($this->documents->reject($documentRequest, $request->user(), $request->validated('rejection_reason'))->load(self::RELATIONS));
    }

    #[OA\Post(
        path: '/document-requests/{documentRequest}/generate',
        summary: 'Generate the PDF for an approved request',
        description: 'Renders from approved grades, GPA and enrollments, stores the PDF privately and issues a verification code. 409 when not approved or when the data does not allow the document (e.g. a transcript without approved grades); the request then stays approved for a retry.',
        operationId: 'generateDocument',
        tags: ['Documents'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'documentRequest', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 201, description: 'Generated.', content: new OA\JsonContent(ref: '#/components/schemas/DocumentRequestResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a manager, or a Department Admin outside the student\'s department.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Not approved, or no data for the document.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function generate(Request $request, DocumentRequest $documentRequest): JsonResponse
    {
        $this->authorize('process', $documentRequest);

        $this->documents->generate($documentRequest, $request->user());

        return (new DocumentRequestResource($documentRequest->refresh()->load(self::RELATIONS)))->response()->setStatusCode(201);
    }

    #[OA\Post(
        path: '/documents/{document}/revoke',
        summary: 'Revoke a generated document',
        description: 'Verification then reports `revoked`; the student may request a fresh document.',
        operationId: 'revokeDocument',
        tags: ['Documents'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'document', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Revoked.', content: new OA\JsonContent(ref: '#/components/schemas/DocumentRequestResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin (revoking is not delegated to Department Admins).', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Already revoked.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function revoke(Document $document): DocumentRequestResource
    {
        $this->authorize('revoke', DocumentRequest::class);

        $this->documents->revoke($document);

        return new DocumentRequestResource($document->request->load(self::RELATIONS));
    }

    #[OA\Get(
        path: '/documents/{document}/download',
        summary: 'Download a generated PDF',
        description: 'Streams the private file to staff or the requesting student.',
        operationId: 'downloadDocument',
        tags: ['Documents'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'document', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'The PDF.', content: new OA\MediaType(mediaType: 'application/pdf', schema: new OA\Schema(type: 'string', format: 'binary'))),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not staff or the requesting student.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function download(Document $document): StreamedResponse
    {
        $this->authorize('view', $document->request);

        return $this->documents->download($document);
    }

    #[OA\Get(
        path: '/verifications/{token}',
        summary: 'Verify a document (public)',
        description: 'No authentication. Returns minimal data for a known code (status valid / revoked), 404 otherwise. Every lookup is logged; rate limited.',
        operationId: 'verifyDocument',
        tags: ['Documents'],
        security: [],
        parameters: [new OA\PathParameter(name: 'token', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Known document.', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/DocumentVerification')])),
            new OA\Response(response: 404, description: 'Unknown code.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 429, description: 'Too many lookups.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function verify(Request $request, string $token): JsonResponse
    {
        $result = $this->documents->verify($token, $request->ip(), $request->userAgent());
        abort_if($result === null, 404, 'No document matches this verification code.');

        return response()->json(['data' => $result]);
    }

    public const RELATIONS = ['type', 'semester.academicYear', 'student', 'document', 'invoice'];

    /** Staff: every request (optionally by status); student: their own. */
    public function listFor(Request $request): LengthAwarePaginator
    {
        $user = $request->user();
        $status = $request->validate(['filters.status' => ['nullable', Rule::in(DocumentRequest::STATUSES)]])['filters']['status'] ?? null;

        $query = DocumentRequest::query()->with(self::RELATIONS)->latest('submitted_at')->latest('id');

        if ($user->can('viewAny', DocumentRequest::class)) {
            $query->visibleTo($user)->when($status, fn ($q) => $q->where('status', $status));
        } elseif ($user->can('create', DocumentRequest::class)) {
            $query->where('student_id', $user->student->getKey())->when($status, fn ($q) => $q->where('status', $status));
        } else {
            abort(403);
        }

        return $query->paginate(15)->withQueryString();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function typeOptions(): array
    {
        return DocumentType::query()->where('is_active', true)->whereIn('code', DocumentType::GENERATABLE)->orderBy('sort_order')->get()
            ->map(fn (DocumentType $type) => [
                'id' => $type->id,
                'code' => $type->code,
                'name' => $type->name,
                'description' => $type->description,
                'requires_fee' => $type->requires_fee,
                'fee_amount' => (float) $type->fee_amount,
                'needs_semester' => $type->needsSemester(),
            ])
            ->values()->all();
    }
}
