<?php

namespace App\Http\Requests;

use App\Models\Assignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create and update share the rules. Due-date-in-the-future, semester bounds
 * and max score vs awarded scores are enforced by `AssignmentService`.
 */
class AssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `AssignmentPolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'instructions' => ['nullable', 'string', 'max:10000'],
            'max_score' => ['required', 'numeric', 'gt:0', 'max:9999.99'],
            'due_at' => ['required', 'date'],
            'assignment_type' => ['required', 'string', Rule::in(Assignment::TYPES)],
            'weight_override' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
