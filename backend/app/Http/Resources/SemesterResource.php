<?php

namespace App\Http\Resources;

use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Semester
 */
class SemesterResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'academic_year_id' => $this->academic_year_id,
            'name' => $this->name,
            'code' => $this->code,
            'sequence' => $this->sequence,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'enrollment_start' => $this->enrollment_start?->toDateString(),
            'enrollment_end' => $this->enrollment_end?->toDateString(),
            'exam_start' => $this->exam_start?->toDateString(),
            'exam_end' => $this->exam_end?->toDateString(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
