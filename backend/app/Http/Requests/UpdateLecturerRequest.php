<?php

namespace App\Http\Requests;

use App\Models\Lecturer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLecturerRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `LecturerPolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        /** @var Lecturer|null $lecturer */
        $lecturer = $this->route('lecturer');

        return [
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($lecturer?->user_id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'staff_number' => ['required', 'string', 'max:50', Rule::unique('lecturers', 'staff_number')->ignore($lecturer?->getKey())],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:50'],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->whereNull('deleted_at')],
            'position' => ['nullable', 'string', 'max:100'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['sometimes', 'string', Rule::in(Lecturer::EMPLOYMENT_TYPES)],
        ];
    }
}
