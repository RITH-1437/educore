<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Compute draft grades for a section, optionally with per-student remarks.
 */
class ComputeGradesRequest extends FormRequest
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
            'remarks' => ['sometimes', 'array'],
            'remarks.*.enrollment_id' => ['required', 'integer'],
            'remarks.*.remarks' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<int, string|null>
     */
    public function remarksByEnrollment(): array
    {
        return collect($this->validated('remarks', []))->mapWithKeys(fn ($row) => [(int) $row['enrollment_id'] => $row['remarks'] ?? null])->all();
    }
}
