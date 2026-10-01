<?php

namespace App\Http\Resources;

use App\Models\Lecturer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Lecturer
 */
class LecturerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
                'is_active' => (bool) $this->user->is_active,
            ] : null),
            'staff_number' => $this->staff_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->fullName(),
            'title' => $this->title,
            'department_id' => $this->department_id,
            'department' => $this->whenLoaded('department', fn () => [
                'id' => $this->department->id,
                'code' => $this->department->code,
                'name' => $this->department->name,
                'faculty_id' => $this->department->faculty_id,
                'faculty' => $this->department->relationLoaded('faculty') && $this->department->faculty
                    ? [
                        'id' => $this->department->faculty->id,
                        'code' => $this->department->faculty->code,
                        'name' => $this->department->faculty->name,
                    ]
                    : null,
            ]),
            'position' => $this->position,
            'specialization' => $this->specialization,
            'employment_type' => $this->employment_type,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
