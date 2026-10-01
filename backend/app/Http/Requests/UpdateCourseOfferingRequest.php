<?php

namespace App\Http\Requests;

use App\Models\CourseOffering;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Course and semester are the offering's identity and cannot change; create a
 * new offering instead.
 */
class UpdateCourseOfferingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `CourseOfferingPolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(CourseOffering::STATUSES)],
            'max_enrollments' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
