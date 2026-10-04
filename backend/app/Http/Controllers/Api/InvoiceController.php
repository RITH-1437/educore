<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateTuitionRequest;
use App\Http\Requests\InvoiceRequest;
use App\Http\Requests\RecordPaymentRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Semester;
use App\Models\Student;
use App\Services\InvoiceService;
use App\Services\TuitionInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Invoices and payment records (module 9.18). No online gateway: payments are
 * records of funds received, entered by administrators.
 */
class InvoiceController extends Controller
{
    public const DETAIL = ['student', 'items', 'payments.receiver:id,name'];

    public function __construct(
        private readonly InvoiceService $invoices,
    ) {}

    #[OA\Get(
        path: '/invoices',
        summary: 'List invoices',
        description: 'Newest first. Past-due unpaid invoices are marked overdue before listing.',
        operationId: 'listInvoices',
        tags: ['Finance'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'filters[status]', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'partial', 'paid', 'overdue', 'cancelled'])),
            new OA\QueryParameter(name: 'filters[student_id]', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'search', required: false, description: 'Invoice number, title, student number or name.', schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'page', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Invoices.', content: new OA\JsonContent(ref: '#/components/schemas/InvoiceCollection')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid filter.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Invoice::class);

        return InvoiceResource::collection($this->invoices->paginate(self::filters($request)));
    }

    #[OA\Post(
        path: '/invoices',
        summary: 'Create an invoice',
        description: 'Totals come from the items (quantity × unit price must be a whole cent); total = subtotal − discount. Numbered `INV-{year}-{sequence}`.',
        operationId: 'createInvoice',
        tags: ['Finance'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/InvoiceRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Created.', content: new OA\JsonContent(ref: '#/components/schemas/InvoiceResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function store(InvoiceRequest $request): JsonResponse
    {
        $this->authorize('manage', Invoice::class);

        return (new InvoiceResource($this->invoices->create($request->validated())->load(self::DETAIL)))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/invoices/{invoice}',
        summary: 'Fetch an invoice with items, payments and balance',
        operationId: 'getInvoice',
        tags: ['Finance'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'invoice', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'The invoice.', content: new OA\JsonContent(ref: '#/components/schemas/InvoiceResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a manager or the invoiced student.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Invoice $invoice): InvoiceResource
    {
        $this->authorize('view', $invoice);
        $this->invoices->refreshOverdue();

        return new InvoiceResource($invoice->refresh()->load(self::DETAIL));
    }

    #[OA\Get(
        path: '/invoices/{invoice}/download',
        summary: 'Download invoice PDF',
        description: 'Returns an official PDF invoice and payment receipt. Accessible by managers and the invoiced student.',
        operationId: 'downloadInvoicePdf',
        tags: ['Finance'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'invoice', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'The invoice PDF.', content: new OA\MediaType(mediaType: 'application/pdf')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a manager or the invoiced student.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function download(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);
        $content = $this->invoices->renderPdf($invoice);
        $filename = "{$invoice->invoice_number}.pdf";

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    #[OA\Put(
        path: '/invoices/{invoice}',
        summary: 'Edit an invoice',
        description: 'Replaces the items. Refused (409) once payments exist or when cancelled. `student_id` cannot change.',
        operationId: 'updateInvoice',
        tags: ['Finance'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'invoice', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/InvoiceRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Updated.', content: new OA\JsonContent(ref: '#/components/schemas/InvoiceResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Has payments, or cancelled.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    #[OA\Patch(
        path: '/invoices/{invoice}',
        summary: 'Edit an invoice (PATCH)',
        description: 'Same full-update validation as PUT.',
        operationId: 'patchInvoice',
        tags: ['Finance'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'invoice', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/InvoiceRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Updated.', content: new OA\JsonContent(ref: '#/components/schemas/InvoiceResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Has payments, or cancelled.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function update(InvoiceRequest $request, Invoice $invoice): InvoiceResource
    {
        $this->authorize('manage', Invoice::class);

        return new InvoiceResource($this->invoices->update($invoice, $request->validated())->load(self::DETAIL));
    }

    #[OA\Post(
        path: '/invoices/{invoice}/cancel',
        summary: 'Cancel an invoice',
        description: 'Invoices are never deleted. Refused (409) while a net amount is paid — reverse the payments first.',
        operationId: 'cancelInvoice',
        tags: ['Finance'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'invoice', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(properties: [new OA\Property(property: 'reason', type: 'string', maxLength: 500, nullable: true)])),
        responses: [
            new OA\Response(response: 200, description: 'Cancelled.', content: new OA\JsonContent(ref: '#/components/schemas/InvoiceResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Already cancelled, or payments recorded.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function cancel(Request $request, Invoice $invoice): InvoiceResource
    {
        $this->authorize('manage', Invoice::class);
        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:500']])['reason'] ?? null;

        return new InvoiceResource($this->invoices->cancel($invoice, $reason)->load(self::DETAIL));
    }

    #[OA\Post(
        path: '/invoices/{invoice}/payments',
        summary: 'Record a received payment',
        description: 'Amount > 0 and at most the remaining balance (no overpayment); date not in the future; not on a cancelled invoice (409). Updates the paid amount and status atomically.',
        operationId: 'recordPayment',
        tags: ['Finance'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'invoice', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/RecordPaymentRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Recorded; the updated invoice.', content: new OA\JsonContent(ref: '#/components/schemas/InvoiceResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Invoice cancelled.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed (e.g. exceeds balance).', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function pay(RecordPaymentRequest $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('manage', Invoice::class);

        $this->invoices->recordPayment($invoice, $request->validated(), $request->user());

        return (new InvoiceResource($invoice->refresh()->load(self::DETAIL)))->response()->setStatusCode(201);
    }

    #[OA\Post(
        path: '/payments/{payment}/reverse',
        summary: 'Reverse a payment',
        description: 'Appends a reversal record (history is never edited or deleted) and reduces the paid amount. A payment is reversed at most once; a reversal cannot be reversed (409).',
        operationId: 'reversePayment',
        tags: ['Finance'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'payment', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['reason'], properties: [new OA\Property(property: 'reason', type: 'string', maxLength: 500)])),
        responses: [
            new OA\Response(response: 200, description: 'Reversed; the updated invoice.', content: new OA\JsonContent(ref: '#/components/schemas/InvoiceResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Already reversed, or a reversal.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Reason missing.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function reverse(Request $request, Payment $payment): InvoiceResource
    {
        $this->authorize('manage', Invoice::class);
        $reason = $request->validate(['reason' => ['required', 'string', 'max:500']])['reason'];

        $this->invoices->reverse($payment, $reason, $request->user());

        return new InvoiceResource($payment->invoice->refresh()->load(self::DETAIL));
    }

    #[OA\Get(
        path: '/students/{student}/invoices',
        summary: "A student's invoices and balance",
        description: 'Managers or the student themself. `summary` has one row per currency (amounts in different currencies are never added) and excludes cancelled invoices.',
        operationId: 'getStudentInvoices',
        tags: ['Finance'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'student', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Invoices with items and payments, plus a summary.', content: new OA\JsonContent(ref: '#/components/schemas/StudentInvoicesResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a manager or the student themself.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function student(Student $student): JsonResponse
    {
        $this->authorize('viewStudent', [Invoice::class, $student]);

        return response()->json($this->studentPayload($student));
    }

    /**
     * @return array{data: mixed, summary: list<array<string, mixed>>}
     */
    public function studentPayload(Student $student): array
    {
        $summary = $this->invoices->summaryFor($student);

        return [
            'data' => InvoiceResource::collection(Invoice::query()->where('student_id', $student->getKey())->with(['items', 'payments'])->latest('issued_date')->latest('id')->get())->resolve(),
            'summary' => $summary,
        ];
    }

    #[OA\Post(
        path: '/invoices/generate-tuition',
        summary: 'Generate automatic tuition invoices',
        description: 'Generates tuition invoices for students enrolled in a semester based on course credits.',
        operationId: 'generateTuitionInvoices',
        tags: ['Finance'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['semester_id'],
                properties: [
                    new OA\Property(property: 'semester_id', type: 'integer', example: 1),
                    new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true, example: '2026-11-15'),
                    new OA\Property(property: 'rate_per_credit', type: 'number', format: 'float', nullable: true, example: 50.0),
                    new OA\Property(property: 'department_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'program_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'dry_run', type: 'boolean', example: false),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Tuition generation result summary.'),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function generateTuition(GenerateTuitionRequest $request, TuitionInvoiceService $service): JsonResponse
    {
        $this->authorize('manage', Invoice::class);
        $semester = Semester::query()->findOrFail($request->validated('semester_id'));

        $result = $service->generate($semester, $request->validated(), $request->user());

        return response()->json([
            'message' => $result['dry_run']
                ? "Preview completed: {$result['generated_count']} invoice(s) projected."
                : "Successfully generated {$result['generated_count']} tuition invoice(s).",
            'data' => $result,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function filters(Request $request): array
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'filters.status' => ['nullable', Rule::in(Invoice::STATUSES)],
            'filters.student_id' => ['nullable', 'integer'],
        ]);

        return ['search' => $validated['search'] ?? null, 'status' => $validated['filters']['status'] ?? null, 'student_id' => $validated['filters']['student_id'] ?? null];
    }
}
