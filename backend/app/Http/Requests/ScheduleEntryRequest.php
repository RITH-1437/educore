<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shape only; ordering, day bounds, room state/capacity and every overlap rule
 * are enforced by `TimetableService`.
 */
class ScheduleEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced through the section's offering policy.
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'integer', Rule::exists('rooms', 'id')],
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
        ];
    }
}
