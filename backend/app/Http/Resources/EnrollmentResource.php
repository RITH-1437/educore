<?php

namespace App\Http\Resources;

use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Enrollment
 */
class EnrollmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $section = $this->relationLoaded('section') ? $this->section : null;
        $offering = $section?->relationLoaded('offering') ? $section->offering : null;
        $course = $offering?->relationLoaded('course') ? $offering->course : null;

        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'student_number' => $this->student->student_number,
                'full_name' => $this->student->fullName(),
            ]),
            'section_id' => $this->section_id,
            'section' => $section ? [
                'id' => $section->id,
                'code' => $section->code,
                'offering_id' => $section->course_offering_id,
                'course' => $course ? ['id' => $course->id, 'code' => $course->code, 'name' => $course->name, 'credits' => (float) $course->credits] : null,
            ] : null,
            'semester_id' => $this->semester_id,
            'semester' => $this->whenLoaded('semester', fn () => [
                'id' => $this->semester->id,
                'name' => $this->semester->name,
                'academic_year' => $this->semester->relationLoaded('academicYear') ? $this->semester->academicYear?->code : null,
            ]),
            'status' => $this->status,
            'enrolled_at' => $this->enrolled_at?->toISOString(),
            'dropped_at' => $this->dropped_at?->toISOString(),
        ];
    }
}
