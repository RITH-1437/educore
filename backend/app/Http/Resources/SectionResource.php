<?php

namespace App\Http\Resources;

use App\Models\Lecturer;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Section
 */
class SectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_offering_id' => $this->course_offering_id,
            'code' => $this->code,
            'name' => $this->name,
            'capacity' => $this->capacity,
            'status' => $this->status,
            'enrolled' => $this->whenHas('enrolled_count'),
            'offering' => $this->whenLoaded('offering', fn () => [
                'id' => $this->offering->id,
                'status' => $this->offering->status,
                'course' => $this->offering->relationLoaded('course') ? [
                    'id' => $this->offering->course->id,
                    'code' => $this->offering->course->code,
                    'name' => $this->offering->course->name,
                    'credits' => (float) $this->offering->course->credits,
                ] : null,
                'semester' => $this->offering->relationLoaded('semester') ? [
                    'id' => $this->offering->semester->id,
                    'name' => $this->offering->semester->name,
                    'status' => $this->offering->semester->status?->value,
                    'academic_year' => $this->offering->semester->relationLoaded('academicYear')
                        ? $this->offering->semester->academicYear?->code
                        : null,
                ] : null,
            ]),
            'schedule' => $this->whenLoaded('scheduleEntries', fn () => ScheduleEntryResource::collection($this->scheduleEntries)->resolve($request)),
            'lecturers' => $this->whenLoaded('lecturers', fn () => $this->lecturers->map(fn (Lecturer $lecturer) => [
                'id' => $lecturer->id,
                'staff_number' => $lecturer->staff_number,
                'full_name' => $lecturer->fullName(),
                'is_active' => $lecturer->is_active,
                'role' => $lecturer->pivot->role,
            ])->values()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
