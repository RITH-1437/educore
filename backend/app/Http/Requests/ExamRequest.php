<?php

namespace App\Http\Requests;

use App\Models\Exam;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create and update share the rules. Semester bounds, the 100% weight cap,
 * time clashes and max score vs recorded scores are enforced by `ExamService`.
 */
class ExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `ExamPolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'exam_type' => ['required', Rule::in(Exam::TYPES)],
            'title' => ['required', 'string', 'max:255'],
            'weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_score' => ['required', 'numeric', 'gt:0', 'max:9999.99'],
            'scheduled_date' => ['nullable', 'date_format:Y-m-d', 'required_with:start_time,end_time'],
            'start_time' => ['nullable', 'date_format:H:i', 'required_with:end_time'],
            'end_time' => ['nullable', 'date_format:H:i', 'required_with:start_time', 'after:start_time'],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }
}
