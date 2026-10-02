<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A student's document request. The student is always the signed-in user
 * (never taken from the body); type and semester rules live in `DocumentService`.
 */
class StoreDocumentRequestRequest extends FormRequest
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
        return [
            'document_type_id' => ['required', 'integer', Rule::exists('document_types', 'id')],
            'semester_id' => ['nullable', 'integer', Rule::exists('semesters', 'id')],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
