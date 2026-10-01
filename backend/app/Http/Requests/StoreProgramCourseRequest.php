<?php

namespace App\Http\Requests;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Adds a course to a program's curriculum (`course_programs`).
 */
class StoreProgramCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `ProgramPolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        $programId = $this->route('program')?->getKey();

        return [
            'course_id' => [
                'required', 'integer',
                // Archived courses cannot join a curriculum.
                Rule::exists('courses', 'id')->where(fn ($query) => $query->where('status', '!=', Course::STATUS_ARCHIVED)),
                // No duplicate course within a program (skill §9).
                Rule::unique('course_programs', 'course_id')->where(fn ($query) => $query->where('program_id', $programId)),
            ],
            'is_required' => ['sometimes', 'boolean'],
            'suggested_semester' => ['nullable', 'integer', 'min:1', 'max:16'],
        ];
    }
}
