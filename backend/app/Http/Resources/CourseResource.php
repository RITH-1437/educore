<?php

namespace App\Http\Resources;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Course
 */
class CourseResource extends JsonResource
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
            'credits' => (float) $this->credits,
            'lecture_hours' => $this->lecture_hours,
            'lab_hours' => $this->lab_hours,
            'description' => $this->description,
            'course_level' => $this->course_level,
            'status' => $this->status,
            'prerequisites_count' => $this->whenCounted('prerequisites'),
            'programs_count' => $this->whenCounted('programs'),
            'prerequisites' => $this->whenLoaded('prerequisites', fn () => $this->prerequisites->map(fn (Course $prerequisite) => [
                'id' => $prerequisite->id,
                'code' => $prerequisite->code,
                'name' => $prerequisite->name,
                'credits' => (float) $prerequisite->credits,
                'status' => $prerequisite->status,
                'is_strict' => (bool) $prerequisite->pivot->is_strict,
            ])->values()),
            'programs' => $this->whenLoaded('programs', fn () => $this->programs->map(fn ($program) => [
                'id' => $program->id,
                'code' => $program->code,
                'name' => $program->name,
                'is_required' => (bool) $program->pivot->is_required,
                'suggested_semester' => $program->pivot->suggested_semester,
            ])->values()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
