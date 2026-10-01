<?php

namespace App\Http\Resources;

use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Program
 */
class ProgramResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
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
            'code' => $this->code,
            'name' => $this->name,
            'degree_level' => $this->degree_level,
            'duration_years' => $this->duration_years,
            'credits_required' => $this->credits_required === null ? null : (float) $this->credits_required,
            'is_active' => $this->is_active,
            'courses_count' => $this->whenCounted('courses'),
            'courses' => $this->whenLoaded('courses', fn () => $this->courses->map(fn ($course) => [
                'id' => $course->id,
                'code' => $course->code,
                'name' => $course->name,
                'credits' => (float) $course->credits,
                'status' => $course->status,
                'is_required' => (bool) $course->pivot->is_required,
                'suggested_semester' => $course->pivot->suggested_semester,
            ])->values()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
