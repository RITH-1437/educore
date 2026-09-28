<?php

namespace App\Http\Requests;

use App\Enums\SemesterStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSemesterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $academicYear = $this->route('academicYear');

        return [
            'name' => ['required', 'string', 'max:50'],
            'code' => ['required', 'string', 'max:20'],
            'sequence' => [
                'required', 'integer', 'min:1', 'max:20',
                Rule::unique('semesters', 'sequence')->where(
                    fn ($query) => $query->where('academic_year_id', $academicYear->getKey())
                ),
            ],
            // The academic-year span is enforced by `SemesterService`, which
            // owns the cross-field rule; duplicating it here would report the
            // same problem as a validation error instead of a 409.
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'enrollment_start' => ['nullable', 'date'],
            'enrollment_end' => ['nullable', 'date', 'after_or_equal:enrollment_start'],
            'exam_start' => ['nullable', 'date'],
            'exam_end' => ['nullable', 'date', 'after_or_equal:exam_start'],
            'status' => ['sometimes', Rule::enum(SemesterStatus::class)],
        ];
    }
}
