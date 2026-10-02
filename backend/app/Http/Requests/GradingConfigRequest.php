<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A course's component weights; the 100 % total is checked by `GradingService`.
 */
class GradingConfigRequest extends FormRequest
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
        $weight = ['required', 'numeric', 'min:0', 'max:100'];

        return [
            'attendance_weight' => $weight,
            'assignment_weight' => $weight,
            'midterm_weight' => $weight,
            'final_weight' => $weight,
            'practical_weight' => $weight,
        ];
    }
}
