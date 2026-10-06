<?php

namespace App\Http\Requests;

use App\Models\Section;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Active-lecturer, duplicate and single-primary rules need the section's
 * current assignments and are enforced by `CourseOfferingService`.
 */
class AssignSectionLecturerRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `CourseOfferingPolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'lecturer_id' => [
                'required', 'integer',
                // A Department Admin assigns only their department's lecturers
                // (report 46); managers may assign any lecturer.
                Rule::exists('lecturers', 'id')->when(
                    $this->user()?->departmentScope() !== null,
                    fn ($rule) => $rule->where('department_id', $this->user()->departmentScope()),
                ),
            ],
            'role' => ['sometimes', 'string', Rule::in(Section::LECTURER_ROLES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->user()?->departmentScope() !== null
            ? ['lecturer_id.exists' => 'Choose a lecturer from your department.']
            : [];
    }
}
