<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `DepartmentPolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        $departmentId = $this->route('department')?->getKey();

        // The uniqueness scope must be the university the department will END
        // UP in. When none is submitted, that is the one it already belongs to —
        // using the request input alone would scope to `NULL` and let a
        // duplicate slip past validation until the database rejects it.
        $universityId = $this->input('university_id') ?? $this->route('department')?->university_id;

        return [
            'university_id' => ['sometimes', 'integer', Rule::exists('universities', 'id')],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('departments', 'code')->ignore($departmentId),
            ],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('departments', 'name')
                    ->where(fn ($query) => $query->where('university_id', $universityId))
                    ->ignore($departmentId),
            ],
            'head_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
