<?php

namespace App\Http\Requests;

use App\Models\CourseOffering;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseOfferingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `CourseOfferingPolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')],
            'semester_id' => [
                'required', 'integer', Rule::exists('semesters', 'id'),
                // One offering per course per semester (uq_course_offerings).
                Rule::unique('course_offerings', 'semester_id')->where(fn ($query) => $query->where('course_id', $this->input('course_id'))),
            ],
            'status' => ['sometimes', 'string', Rule::in(CourseOffering::STATUSES)],
            'max_enrollments' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['semester_id.unique' => 'This course is already offered in that semester.'];
    }
}
