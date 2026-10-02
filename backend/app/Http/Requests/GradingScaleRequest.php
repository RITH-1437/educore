<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Bands of the active grading scale. Coverage (lowest band starts at 0 %) and
 * monotonic grade points are checked by `GradingService::saveScale()`.
 */
class GradingScaleRequest extends FormRequest
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
            'bands' => ['required', 'array', 'min:2', 'max:20'],
            'bands.*.grade' => ['required', 'string', 'max:5', 'distinct:ignore_case'],
            'bands.*.min_percentage' => ['required', 'numeric', 'min:0', 'max:100', 'distinct'],
            'bands.*.grade_point' => ['required', 'numeric', 'min:0', 'max:5'],
            'bands.*.is_pass' => ['sometimes', 'boolean'],
        ];
    }
}
