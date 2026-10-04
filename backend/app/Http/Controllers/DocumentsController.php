<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\DocumentController as ApiDocumentController;
use App\Http\Requests\RejectDocumentRequestRequest;
use App\Http\Requests\StoreDocumentRequestRequest;
use App\Http\Requests\WaiveDocumentFeeRequest;
use App\Http\Resources\DocumentRequestResource;
use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\Enrollment;
use App\Services\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Document screens (modules 9.16 / 9.17): the student's requests, the staff
 * processing queue, authorized downloads and the public verification page.
 */
class DocumentsController extends Controller
{
    public function __construct(
        private readonly DocumentService $documents,
        private readonly ApiDocumentController $api,
    ) {}

    /** Staff queue. */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', DocumentRequest::class);

        return Inertia::render('Documents/Index', [
            'requests' => DocumentRequestResource::collection($this->api->listFor($request)),
            'filters' => ['status' => $request->input('filters.status')],
            'statuses' => DocumentRequest::STATUSES,
            // Rows are limited to the viewer's scope, so every visible request can be processed.
            'canProcess' => $request->user()->can('processAny', DocumentRequest::class),
            'canRevoke' => $request->user()->can('revoke', DocumentRequest::class),
            'canWaive' => $request->user()->can('waiveFeeAny', DocumentRequest::class),
        ]);
    }

    /** Student: own requests + request form. */
    public function mine(Request $request): Response
    {
        $this->authorize('create', DocumentRequest::class);
        $student = $request->user()->student;

        return Inertia::render('Documents/Mine', [
            'requests' => DocumentRequestResource::collection($this->api->listFor($request)),
            'types' => ApiDocumentController::typeOptions(),
            // Semesters the student has completed courses in (academic results).
            'semesters' => Enrollment::query()
                ->where('student_id', $student->getKey())
                ->whereHas('grade', fn ($q) => $q->whereIn('status', ['approved', 'finalized']))
                ->with('semester.academicYear:id,code')
                ->get()
                ->pluck('semester')
                ->unique('id')
                ->map(fn ($semester) => ['id' => $semester->id, 'name' => trim(($semester->academicYear?->code ?? '').' '.$semester->name)])
                ->values(),
        ]);
    }

    public function store(StoreDocumentRequestRequest $request): RedirectResponse
    {
        $this->authorize('create', DocumentRequest::class);

        $this->documents->request($request->user()->student, $request->validated());

        return back()->with('success', 'Request submitted. You will be able to download the document once it is generated.');
    }

    public function approve(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        $this->authorize('process', $documentRequest);

        $this->documents->approve($documentRequest, $request->user());

        return back()->with('success', 'Request approved.');
    }

    public function reject(RejectDocumentRequestRequest $request, DocumentRequest $documentRequest): RedirectResponse
    {
        $this->authorize('process', $documentRequest);

        $this->documents->reject($documentRequest, $request->user(), $request->validated('rejection_reason'));

        return back()->with('success', 'Request rejected.');
    }

    public function generate(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        $this->authorize('process', $documentRequest);

        $this->documents->generate($documentRequest, $request->user());

        return back()->with('success', 'Document generated.');
    }

    public function waiveFee(WaiveDocumentFeeRequest $request, DocumentRequest $documentRequest): RedirectResponse
    {
        $this->authorize('waiveFee', $documentRequest);

        $this->documents->waiveFee($documentRequest, $request->user(), $request->validated('reason'));

        return back()->with('success', 'Document fee waived.');
    }

    public function revoke(Document $document): RedirectResponse
    {
        $this->authorize('revoke', DocumentRequest::class);

        $this->documents->revoke($document);

        return back()->with('success', 'Document revoked. Verification now reports it as revoked.');
    }

    public function download(Document $document): StreamedResponse
    {
        $this->authorize('view', $document->request);

        return $this->documents->download($document);
    }

    /** Public verification page (9.17). */
    public function verify(Request $request, string $token): Response
    {
        return Inertia::render('Documents/Verify', [
            'token' => $token,
            'result' => $this->documents->verify($token, $request->ip(), $request->userAgent()),
        ]);
    }
}
