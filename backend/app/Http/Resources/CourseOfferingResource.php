<?php

namespace App\Http\Resources;

use App\Models\CourseOffering;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CourseOffering
 */
class CourseOfferingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'semester_id' => $this->semester_id,
            'course' => $this->whenLoaded('course', fn () => [
                'id' => $this->course->id,
                'code' => $this->course->code,
                'name' => $this->course->name,
                'credits' => (float) $this->course->credits,
            ]),
            'semester' => $this->whenLoaded('semester', fn () => [
                'id' => $this->semester->id,
                'name' => $this->semester->name,
                'code' => $this->semester->code,
                'status' => $this->semester->status?->value,
                'academic_year' => $this->semester->relationLoaded('academicYear') && $this->semester->academicYear
                    ? ['id' => $this->semester->academicYear->id, 'code' => $this->semester->academicYear->code, 'name' => $this->semester->academicYear->name]
                    : null,
            ]),
            'status' => $this->status,
            'max_enrollments' => $this->max_enrollments,
            'notes' => $this->notes,
            'sections_count' => $this->whenCounted('sections'),
            'total_capacity' => $this->whenAggregated('sections', 'capacity', 'sum', fn ($value) => (int) $value),
            // Resolved eagerly so Inertia (which serializes `resolve()`d arrays)
            // receives a plain list, not a resource wrapper.
            'sections' => $this->whenLoaded('sections', fn () => SectionResource::collection($this->sections)->resolve($request)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
