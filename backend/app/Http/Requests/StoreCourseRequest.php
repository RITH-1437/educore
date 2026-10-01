<?php

namespace App\Http\Requests;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `CoursePolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->whereNull('deleted_at')],
            'code' => ['required', 'string', 'max:20', Rule::unique('courses', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'credits' => ['required', 'numeric', 'gt:0', 'max:99.99'],
            'lecture_hours' => ['nullable', 'integer', 'min:0', 'max:500'],
            'lab_hours' => ['nullable', 'integer', 'min:0', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],
            'course_level' => ['nullable', 'string', Rule::in(Course::LEVELS)],
            'status' => ['sometimes', 'string', Rule::in(Course::EDITABLE_STATUSES)],
        ];
    }
}
