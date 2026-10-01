<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Edits the curriculum placement of a course already in the program.
 */
class UpdateProgramCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `ProgramPolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'is_required' => ['sometimes', 'boolean'],
            'suggested_semester' => ['nullable', 'integer', 'min:1', 'max:16'],
        ];
    }
}
