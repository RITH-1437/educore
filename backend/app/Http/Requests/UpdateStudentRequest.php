<?php

namespace App\Http\Requests;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Profile and contact edits. Status and program have their own actions
 * (`ChangeStudentStatusRequest`, `ChangeStudentProgramRequest`).
 */
class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `StudentPolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        /** @var Student|null $student */
        $student = $this->route('student');

        return [
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($student?->user_id)],
            'phone' => ['nullable', 'string', 'max:50'],
            ...StoreStudentRequest::profileRules(),
            'student_number' => [
                'required', 'string', 'regex:'.StoreStudentRequest::STUDENT_NUMBER_FORMAT,
                Rule::unique('students', 'student_number')->ignore($student?->getKey()),
            ],
            'national_id' => ['nullable', 'string', 'max:50', Rule::unique('students', 'national_id')->ignore($student?->getKey())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'student_number.regex' => 'The student ID may contain only letters, digits and dashes (4–50 characters).',
        ];
    }
}
