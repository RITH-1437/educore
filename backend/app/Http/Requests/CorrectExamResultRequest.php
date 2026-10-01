<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CorrectExamResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `ExamPolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'score' => ['present', 'nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
