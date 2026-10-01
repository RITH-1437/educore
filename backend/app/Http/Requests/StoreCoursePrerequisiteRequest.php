<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Duplicate, self-reference, archived and cyclic prerequisites are rejected by
 * `CourseService::addPrerequisite()` (they need the prerequisite graph); this
 * request only validates the payload shape.
 */
class StoreCoursePrerequisiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `CoursePolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'prerequisite_course_id' => ['required', 'integer', Rule::exists('courses', 'id')],
            'is_strict' => ['sometimes', 'boolean'],
        ];
    }
}
