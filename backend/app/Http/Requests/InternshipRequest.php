<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Internship application fields (lean company snapshot lives on the company
 * record; supervisor details on the internship). The company must be active —
 * checked by `InternshipService`.
 */
class InternshipRequest extends FormRequest
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
            'company_id' => ['required', 'integer', Rule::exists('internship_companies', 'id')],
            'position_title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'supervisor_name' => ['required', 'string', 'max:150'],
            'supervisor_email' => ['nullable', 'email', 'max:150'],
            'supervisor_phone' => ['nullable', 'string', 'max:50'],
        ];
    }
}
