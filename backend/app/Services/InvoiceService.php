<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\University;
use App\Models\User;
use App\Notifications\InvoiceIssued;
use App\Notifications\PaymentRecorded;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Invoices and payment records (module 9.18, `skills/invoices-payments`).
 *
 * - Money is handled in integer cents; the DB stores decimals.
 * - Totals come from the items: subtotal = Σ quantity × unit price,
 *   total = subtotal − discount (discount ≤ subtotal).
 * - Status is derived in one place (`deriveStatus`) and persisted on every
 *   change: cancelled stays; otherwise paid when nothing is owed, overdue when
 *   past due, partial when something was paid, else pending. Past-due rows
 *   are refreshed by `refreshOverdue()` (on read and by a daily command).
 * - Items can change only while nothing has been paid and the invoice is not
 *   cancelled. Invoices are cancelled, never deleted; cancelling needs a net
 *   paid amount of zero.
 * - Payments: amount > 0 and ≤ balance (no overpayment), not in the future,
 *   not on a cancelled invoice. Corrections are reversal rows; a payment is
 *   reversed at most once and a reversal cannot be reversed.
 */
class InvoiceService
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $filters  status, search, student_id
     * @return LengthAwarePaginator<int, Invoice>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->query($filters)->paginate(15)->withQueryString();
    }

    /**
     * The filtered, ordered invoice list (shared by the list and the CSV export).
     *
     * @param  array<string, mixed>  $filters  search, status, student_id
     * @return Builder<Invoice>
     */
    public function query(array $filters): Builder
    {
        $this->refreshOverdue();

        return Invoice::query()
            ->with('student:id,student_number,first_name,last_name')
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['student_id'] ?? null, fn ($q, $id) => $q->where('student_id', $id))
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where(fn ($w) => $w
                ->where('invoice_number', 'ilike', "%{$search}%")
                ->orWhere('title', 'ilike', "%{$search}%")
                ->orWhereHas('student', fn ($s) => $s->where('student_number', 'ilike', "%{$search}%")->orWhereRaw("concat(first_name, ' ', last_name) ilike ?", ["%{$search}%"]))))
            ->latest('issued_date')
            ->latest('id');
    }

    /**
     * @param  array<string, mixed>  $data  student_id, title, description, currency, due_date, issued_date, discount, notes, items[]
     */
    public function create(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $student = Student::query()->findOrFail($data['student_id']);
            [$items, $subtotal, $discount] = $this->prepareItems($data['items'], $data['discount'] ?? 0);
            $issued = Carbon::parse($data['issued_date'] ?? today());
            $this->assertDates($issued, Carbon::parse($data['due_date']));

            $invoice = Invoice::query()->create([
                'student_id' => $student->getKey(),
                'invoice_number' => $this->nextNumber($issued),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'currency' => $data['currency'] ?? 'USD',
                'subtotal' => $subtotal / 100,
                'discount' => $discount / 100,
                'total' => ($subtotal - $discount) / 100,
                'amount_paid' => 0,
                'status' => Invoice::STATUS_PENDING,
                'issued_date' => $issued->toDateString(),
                'due_date' => $data['due_date'],
                'notes' => $data['notes'] ?? null,
            ]);
            $invoice->items()->createMany($items);
            $this->syncStatus($invoice);
            $student->user?->notify(new InvoiceIssued($invoice->refresh()));
            $this->audit->record('invoice.created', $invoice, after: $invoice->only(['invoice_number', 'student_id', 'currency', 'subtotal', 'discount', 'total', 'due_date']));

            return $invoice;
        });
    }

    /**
     * @param  array<string, mixed>  $data  same as create, without student_id
     */
    public function update(Invoice $invoice, array $data): Invoice
    {
        return DB::transaction(function () use ($invoice, $data) {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->getKey());

            if ($invoice->status === Invoice::STATUS_CANCELLED) {
                throw new BusinessRuleException('A cancelled invoice cannot be edited.');
            }

            if (Invoice::cents($invoice->amount_paid) > 0 || $invoice->payments()->exists()) {
                throw new BusinessRuleException('An invoice with recorded payments cannot be edited; reverse the payments first or issue a new invoice.');
            }

            $before = $invoice->getAttributes();
            [$items, $subtotal, $discount] = $this->prepareItems($data['items'], $data['discount'] ?? 0);
            $issued = Carbon::parse($data['issued_date'] ?? $invoice->issued_date);
            $this->assertDates($issued, Carbon::parse($data['due_date']));

            $invoice->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'currency' => $data['currency'] ?? $invoice->currency,
                'subtotal' => $subtotal / 100,
                'discount' => $discount / 100,
                'total' => ($subtotal - $discount) / 100,
                'issued_date' => $issued->toDateString(),
                'due_date' => $data['due_date'],
                'notes' => $data['notes'] ?? null,
            ]);
            $invoice->items()->delete();
            $invoice->items()->createMany($items);
            $this->syncStatus($invoice);
            $this->audit->changes('invoice.updated', $invoice->refresh(), $before);

            return $invoice->refresh();
        });
    }

    public function cancel(Invoice $invoice, ?string $reason): Invoice
    {
        return DB::transaction(function () use ($invoice, $reason) {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->getKey());

            if ($invoice->status === Invoice::STATUS_CANCELLED) {
                throw new BusinessRuleException('The invoice is already cancelled.');
            }

            if (Invoice::cents($invoice->amount_paid) > 0) {
                throw new BusinessRuleException('Payments have been recorded; reverse them before cancelling the invoice.');
            }

            $this->audit->record('invoice.cancelled', $invoice, ['status' => $invoice->status], ['status' => Invoice::STATUS_CANCELLED], $reason);
            $invoice->update([
                'status' => Invoice::STATUS_CANCELLED,
                'notes' => trim(($invoice->notes ? $invoice->notes."\n" : '').'Cancelled '.today()->toDateString().($reason ? ": {$reason}" : '')),
            ]);

            return $invoice->refresh();
        });
    }

    /**
     * @param  array{amount: float|int|string, paid_on: string, method: string, reference?: string|null, notes?: string|null}  $data
     */
    public function recordPayment(Invoice $invoice, array $data, User $by): Payment
    {
        return DB::transaction(function () use ($invoice, $data, $by) {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->getKey());
            $amount = Invoice::cents($data['amount']);

            if ($invoice->status === Invoice::STATUS_CANCELLED) {
                throw new BusinessRuleException('Payments cannot be recorded on a cancelled invoice.');
            }

            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'The amount must be greater than zero.']);
            }

            if ($amount > $invoice->balanceCents()) {
                throw ValidationException::withMessages(['amount' => 'The amount exceeds the remaining balance of '.number_format($invoice->balanceCents() / 100, 2).' '.$invoice->currency.'.']);
            }

            if (Carbon::parse($data['paid_on'])->isAfter(today())) {
                throw ValidationException::withMessages(['paid_on' => 'The payment date cannot be in the future.']);
            }

            $payment = $invoice->payments()->create([
                'amount' => $amount / 100,
                'paid_on' => $data['paid_on'],
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'received_by' => $by->getKey(),
                'is_reversal' => false,
            ]);

            $invoice->update(['amount_paid' => (Invoice::cents($invoice->amount_paid) + $amount) / 100]);
            $this->syncStatus($invoice);
            $invoice->student->user?->notify(new PaymentRecorded($payment->refresh()));
            $this->audit->record('payment.recorded', $payment, after: $payment->only(['invoice_id', 'amount', 'paid_on', 'method', 'reference']));

            return $payment;
        });
    }

    /** Append a reversal row for a payment and reduce the paid amount. */
    public function reverse(Payment $payment, string $reason, User $by): Payment
    {
        return DB::transaction(function () use ($payment, $reason, $by) {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($payment->invoice_id);
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());

            if ($payment->is_reversal) {
                throw new BusinessRuleException('A reversal cannot itself be reversed; record a new payment instead.');
            }

            if ($payment->reversal()->exists()) {
                throw new BusinessRuleException('This payment has already been reversed.');
            }

            $reversal = $invoice->payments()->create([
                'amount' => $payment->amount,
                'paid_on' => today()->toDateString(),
                'method' => $payment->method,
                'reference' => $payment->reference,
                'notes' => $reason,
                'received_by' => $by->getKey(),
                'is_reversal' => true,
                'reversal_of' => $payment->getKey(),
            ]);

            $invoice->update(['amount_paid' => (Invoice::cents($invoice->amount_paid) - Invoice::cents($payment->amount)) / 100]);
            $this->syncStatus($invoice);
            $invoice->student->user?->notify(new PaymentRecorded($reversal->refresh()));
            $this->audit->record('payment.reversed', $payment, $payment->only(['invoice_id', 'amount', 'paid_on', 'method']), ['reversal_id' => $reversal->id], $reason);

            return $reversal;
        });
    }

    /** Persist the derived status for unpaid invoices now past due. */
    public function refreshOverdue(): int
    {
        return Invoice::query()
            ->whereIn('status', [Invoice::STATUS_PENDING, Invoice::STATUS_PARTIAL])
            ->whereDate('due_date', '<', today())
            ->update(['status' => Invoice::STATUS_OVERDUE, 'updated_at' => now()]);
    }

    /**
     * Totals per currency (amounts in different currencies are never added).
     * Cancelled invoices are excluded.
     *
     * @return list<array{currency: string, invoiced: float, paid: float, balance: float, overdue: float, count: int}>
     */
    public function summaryFor(Student $student): array
    {
        $this->refreshOverdue();

        return Invoice::query()->where('student_id', $student->getKey())->where('status', '!=', Invoice::STATUS_CANCELLED)
            ->groupBy('currency')
            ->orderBy('currency', 'desc')
            ->select('currency')
            ->selectRaw('sum(total) as invoiced, sum(amount_paid) as paid, count(*) as count')
            ->selectRaw("coalesce(sum(total - amount_paid) filter (where status = 'overdue'), 0) as overdue")
            ->get()
            ->map(fn ($row) => [
                'currency' => $row->currency,
                'invoiced' => (float) $row->invoiced,
                'paid' => (float) $row->paid,
                'balance' => round((float) $row->invoiced - (float) $row->paid, 2),
                'overdue' => (float) $row->overdue,
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();
    }

    public static function deriveStatus(Invoice $invoice): string
    {
        return match (true) {
            $invoice->status === Invoice::STATUS_CANCELLED => Invoice::STATUS_CANCELLED,
            $invoice->balanceCents() <= 0 => Invoice::STATUS_PAID,
            $invoice->due_date->isBefore(today()) => Invoice::STATUS_OVERDUE,
            Invoice::cents($invoice->amount_paid) > 0 => Invoice::STATUS_PARTIAL,
            default => Invoice::STATUS_PENDING,
        };
    }

    private function syncStatus(Invoice $invoice): void
    {
        $invoice->refresh();
        $status = self::deriveStatus($invoice);

        if ($status !== $invoice->status) {
            $invoice->update(['status' => $status]);
        }
    }

    /**
     * Items in cents; the line amount must be exact to the cent because the DB
     * checks amount = quantity × unit_price.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array{0: list<array<string, mixed>>, 1: int, 2: int}
     */
    private function prepareItems(array $rows, float|int|string $discount): array
    {
        $items = [];
        $subtotal = 0;

        foreach ($rows as $i => $row) {
            $quantity = Invoice::cents($row['quantity'] ?? 1);
            $price = Invoice::cents($row['unit_price']);
            $product = $quantity * $price; // in 1/10 000

            if ($product % 100 !== 0) {
                throw ValidationException::withMessages(["items.{$i}.quantity" => 'Quantity × unit price must come to a whole cent.']);
            }

            $amount = intdiv($product, 100);
            $subtotal += $amount;
            $items[] = [
                'description' => $row['description'],
                'quantity' => $quantity / 100,
                'unit_price' => $price / 100,
                'amount' => $amount / 100,
                'fee_category' => $row['fee_category'] ?? null,
            ];
        }

        $discountCents = Invoice::cents($discount);

        if ($discountCents > $subtotal) {
            throw ValidationException::withMessages(['discount' => 'The discount cannot exceed the subtotal.']);
        }

        return [$items, $subtotal, $discountCents];
    }

    private function assertDates(Carbon $issued, Carbon $due): void
    {
        if ($due->isBefore($issued)) {
            throw ValidationException::withMessages(['due_date' => 'The due date cannot be before the issue date.']);
        }
    }

    /** `INV-{year}-{5-digit sequence}`, serialized by a transaction-scoped advisory lock. */
    private function nextNumber(Carbon $issued): string
    {
        DB::select('SELECT pg_advisory_xact_lock(918)');
        $prefix = 'INV-'.$issued->year.'-';
        $last = Invoice::withTrashed()->where('invoice_number', 'like', $prefix.'%')->max('invoice_number');

        return $prefix.str_pad((string) ((int) substr((string) $last, strlen($prefix)) + 1), 5, '0', STR_PAD_LEFT);
    }

    /**
     * Render the invoice PDF document.
     */
    public function renderPdf(Invoice $invoice): string
    {
        $this->refreshOverdue();
        $invoice->loadMissing([
            'student.user',
            'student.currentProgram.program.department',
            'items',
            'payments.receiver:id,name',
        ]);
        $university = University::query()->where('is_current', true)->first();

        return Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'student' => $invoice->student,
            'university' => $university,
            'issuedOn' => now()->toDateString(),
        ])->setPaper('a4')->setOption('isFontSubsettingEnabled', true)->output();
    }
}
