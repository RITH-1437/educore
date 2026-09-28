<?php

namespace App\Http\Requests;

use App\Enums\AcademicYearStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $academicYearId = $this->route('academicYear')?->getKey();

        return [
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('academic_years', 'code')->ignore($academicYearId),
            ],
            'name' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['sometimes', Rule::enum(AcademicYearStatus::class)],
            'is_current' => ['sometimes', 'boolean'],
        ];
    }
}
