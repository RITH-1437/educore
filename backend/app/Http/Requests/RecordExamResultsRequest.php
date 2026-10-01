<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Bulk results. Membership of the section and the max score are checked by
 * `ExamService::record`.
 */
class RecordExamResultsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `ExamPolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'results' => ['required', 'array', 'min:1', 'max:500'],
            'results.*.enrollment_id' => ['required', 'integer', 'distinct'],
            'results.*.score' => ['nullable', 'numeric', 'min:0'],
            'results.*.remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
