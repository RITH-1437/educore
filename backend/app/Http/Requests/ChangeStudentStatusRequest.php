<?php

namespace App\Http\Requests;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Whether the transition is allowed is decided by `StudentService`
 * (`Student::TRANSITIONS`); this only validates the payload.
 */
class ChangeStudentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `StudentPolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(Student::STATUSES)],
            'effective_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
