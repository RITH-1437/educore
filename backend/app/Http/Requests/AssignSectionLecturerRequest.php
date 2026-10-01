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
            'lecturer_id' => ['required', 'integer', Rule::exists('lecturers', 'id')],
            'role' => ['sometimes', 'string', Rule::in(Section::LECTURER_ROLES)],
        ];
    }
}
