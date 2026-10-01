<?php

namespace App\Http\Requests;

use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create and update share the rules; the code must stay unique.
 */
class RoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `RoomPolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('rooms', 'code')->ignore($this->route('room')?->getKey())],
            'name' => ['required', 'string', 'max:100'],
            'building' => ['nullable', 'string', 'max:100'],
            'floor' => ['nullable', 'string', 'max:20'],
            'capacity' => ['required', 'integer', 'min:1', 'max:5000'],
            'room_type' => ['required', 'string', Rule::in(Room::TYPES)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
