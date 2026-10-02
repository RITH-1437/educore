<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\InvoiceController as ApiInvoiceController;
use App\Http\Requests\InvoiceRequest;
use App\Http\Requests\RecordPaymentRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Student;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Finance screens (module 9.18): invoice list, create / edit, the invoice page
 * with payments, and the student's own invoices.
 */
class InvoicesController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly ApiInvoiceController $api,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);
        $filters = ApiInvoiceController::filters($request);

        return Inertia::render('Invoices/Index', [
            'invoices' => InvoiceResource::collection($this->invoices->paginate($filters)),
            'filters' => $filters,
            'statuses' => Invoice::STATUSES,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('manage', Invoice::class);

        return Inertia::render('Invoices/Form', [...$this->formOptions(), 'invoice' => null]);
    }

    public function store(InvoiceRequest $request): RedirectResponse
    {
        $this->authorize('manage', Invoice::class);

        $invoice = $this->invoices->create($request->validated());

        return redirect()->route('invoices.show', $invoice)->with('success', "Invoice {$invoice->invoice_number} created.");
    }

    public function show(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);
        $this->invoices->refreshOverdue();

        return Inertia::render('Invoices/Show', [
            'invoice' => (new InvoiceResource($invoice->refresh()->load(ApiInvoiceController::DETAIL)))->resolve(),
            'methods' => Payment::METHODS,
            'today' => today()->toDateString(),
        ]);
    }

    public function edit(Invoice $invoice): Response
    {
        $this->authorize('manage', Invoice::class);

        return Inertia::render('Invoices/Form', [...$this->formOptions(), 'invoice' => (new InvoiceResource($invoice->load('student', 'items')))->resolve()]);
    }

    public function update(InvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('manage', Invoice::class);

        $this->invoices->update($invoice, $request->validated());

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice updated.');
    }

    public function cancel(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('manage', Invoice::class);

        $this->invoices->cancel($invoice, $request->validate(['reason' => ['nullable', 'string', 'max:500']])['reason'] ?? null);

        return back()->with('success', 'Invoice cancelled.');
    }

    public function pay(RecordPaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('manage', Invoice::class);

        $this->invoices->recordPayment($invoice, $request->validated(), $request->user());

        return back()->with('success', 'Payment recorded.');
    }

    public function reverse(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('manage', Invoice::class);

        $this->invoices->reverse($payment, $request->validate(['reason' => ['required', 'string', 'max:500']])['reason'], $request->user());

        return back()->with('success', 'Payment reversed.');
    }

    /** Student: own invoices, payments and balance. */
    public function mine(Request $request): Response
    {
        $student = $request->user()->student;
        abort_if($student === null, 403, 'No student profile is linked to this account.');

        return Inertia::render('Invoices/Mine', $this->api->studentPayload($student));
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'students' => Student::query()->orderBy('student_number')->get(['id', 'student_number', 'first_name', 'last_name'])
                ->map(fn (Student $student) => ['id' => $student->id, 'label' => "{$student->student_number} — {$student->fullName()}"])->values(),
            'currencies' => Invoice::CURRENCIES,
            'categories' => InvoiceItem::CATEGORIES,
            'today' => today()->toDateString(),
        ];
    }
}
