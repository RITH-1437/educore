<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create / edit an invoice. `student_id` is required on create and ignored on
 * edit (an invoice never moves to another student). Totals, the whole-cent
 * line rule and the discount cap are checked by `InvoiceService`.
 */
class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return [
            'student_id' => [$creating ? 'required' : 'prohibited', 'integer', Rule::exists('students', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'currency' => ['nullable', Rule::in(Invoice::CURRENCIES)],
            'issued_date' => ['nullable', 'date'],
            'due_date' => ['required', 'date'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'items.*.fee_category' => ['nullable', Rule::in(InvoiceItem::CATEGORIES)],
        ];
    }
}
