<?php

namespace App\Http\Requests;

use App\Models\Section;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `CourseOfferingPolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        $offeringId = $this->route('offering')?->getKey();

        return [
            'code' => [
                'required', 'string', 'max:20', 'regex:/^[A-Za-z0-9-]+$/',
                // Unique within the offering (uq_sections_offering_code).
                Rule::unique('sections', 'code')->where(fn ($query) => $query->where('course_offering_id', $offeringId)),
            ],
            'name' => ['nullable', 'string', 'max:100'],
            'capacity' => ['required', 'integer', 'min:1', 'max:1000'],
            'status' => ['sometimes', 'string', Rule::in(Section::STATUSES)],
        ];
    }
}
