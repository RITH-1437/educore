<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeStudentProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `StudentPolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'program_id' => ['required', 'integer', Rule::exists('programs', 'id')->where('is_active', true)],
            'effective_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['program_id.exists' => 'Choose an active program.'];
    }
}
