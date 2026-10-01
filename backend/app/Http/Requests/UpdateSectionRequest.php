<?php

namespace App\Http\Requests;

use App\Models\Section;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `CourseOfferingPolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        /** @var Section|null $section */
        $section = $this->route('section');

        return [
            'code' => [
                'required', 'string', 'max:20', 'regex:/^[A-Za-z0-9-]+$/',
                Rule::unique('sections', 'code')
                    ->where(fn ($query) => $query->where('course_offering_id', $section?->course_offering_id))
                    ->ignore($section?->getKey()),
            ],
            'name' => ['nullable', 'string', 'max:100'],
            'capacity' => ['required', 'integer', 'min:1', 'max:1000'],
            'status' => ['required', 'string', Rule::in(Section::STATUSES)],
        ];
    }
}
