<?php

namespace App\Http\Requests;

use App\Models\InternshipReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** A report with an optional private file (PDF / DOCX, size capped like submissions). */
class InternshipReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'report_type' => ['required', Rule::in(InternshipReport::TYPES)],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:10000'],
            'file' => ['nullable', 'file', 'mimes:pdf,docx', 'max:'.config('academics.submission_max_kb')],
        ];
    }
}
