<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFacultyRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `FacultyPolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'university_id' => ['required', 'integer', Rule::exists('universities', 'id')],
            'code' => ['required', 'string', 'max:50', Rule::unique('faculties', 'code')],
            'name' => ['required', 'string', 'max:255', Rule::unique('faculties', 'name')],
            'dean_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
