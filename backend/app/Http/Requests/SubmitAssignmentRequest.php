<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Type and size are validated server-side before anything is stored
 * (`skills/file-storage`): pdf, docx, zip, png, jpg — at most 10 MB by default.
 */
class SubmitAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `AssignmentPolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required', 'file',
                'mimes:'.implode(',', config('academics.submission_mimes')),
                'max:'.config('academics.submission_max_kb'),
            ],
        ];
    }
}
