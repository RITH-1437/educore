<?php

namespace App\Http\Requests;

use App\Models\AttendanceRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Bulk attendance for one date. Membership of each enrollment in the section,
 * and the date policy, are enforced by `AttendanceService`.
 */
class RecordAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `AttendancePolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'session_date' => ['required', 'date_format:Y-m-d'],
            'topic' => ['nullable', 'string', 'max:255'],
            'records' => ['required', 'array', 'min:1', 'max:500'],
            'records.*.enrollment_id' => ['required', 'integer', 'distinct'],
            'records.*.status' => ['required', 'string', Rule::in(AttendanceRecord::STATUSES)],
            'records.*.remarks' => ['nullable', 'string', 'max:500'],
        ];
    }
}
