<?php

namespace App\Http\Resources;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An invoice with its balance; items and payments when loaded.
 *
 * @mixin Invoice
 */
class InvoiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'title' => $this->title,
            'description' => $this->description,
            'currency' => $this->currency,
            'subtotal' => (float) $this->subtotal,
            'discount' => (float) $this->discount,
            'total' => (float) $this->total,
            'amount_paid' => (float) $this->amount_paid,
            'balance' => $this->balanceCents() / 100,
            'status' => $this->status,
            'issued_date' => $this->issued_date->toDateString(),
            'due_date' => $this->due_date->toDateString(),
            'notes' => $this->notes,
            'student' => $this->whenLoaded('student', fn () => ['id' => $this->student->id, 'student_number' => $this->student->student_number, 'full_name' => $this->student->fullName()]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn (InvoiceItem $item) => [
                'id' => $item->id,
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'amount' => (float) $item->amount,
                'fee_category' => $item->fee_category,
            ])->values()),
            'payments' => $this->whenLoaded('payments', fn () => $this->payments->map(fn (Payment $payment) => [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'paid_on' => $payment->paid_on->toDateString(),
                'method' => $payment->method,
                'reference' => $payment->reference,
                'notes' => $payment->notes,
                'is_reversal' => $payment->is_reversal,
                'reversal_of' => $payment->reversal_of,
                'reversed' => $this->payments->contains('reversal_of', $payment->id),
                'received_by' => $payment->relationLoaded('receiver') ? $payment->receiver?->name : null,
            ])->values()),
        ];
    }
}
