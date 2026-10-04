<?php

namespace App\Http\Requests;

use App\Models\University;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `DepartmentPolicy` in the controller.
        return true;
    }

    /**
     * EduCore runs one university at a time, so a form that does not pick one
     * gets the current university.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('university_id')) {
            $this->merge(['university_id' => University::query()->current()->value('id')]);
        }
    }

    public function rules(): array
    {
        return [
            'university_id' => ['required', 'integer', Rule::exists('universities', 'id')],
            'code' => ['required', 'string', 'max:50', Rule::unique('departments', 'code')],
            'name' => [
                'required', 'string', 'max:255',
                // Unique within the university (`uq_departments_university_id_name`).
                Rule::unique('departments', 'name')->where(
                    fn ($query) => $query->where('university_id', $this->input('university_id'))
                ),
            ],
            'head_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'university_id.required' => 'Create a university first — departments belong to one.',
        ];
    }
}
