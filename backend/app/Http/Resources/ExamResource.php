<?php

namespace App\Http\Resources;

use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Exam schedule and settings; `results_count` for staff views.
 *
 * @mixin Exam
 */
class ExamResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'section_id' => $this->section_id,
            'exam_type' => $this->exam_type,
            'title' => $this->title,
            'weight' => (float) $this->weight,
            'max_score' => (float) $this->max_score,
            'scheduled_date' => $this->scheduled_date?->toDateString(),
            'start_time' => $this->start_time ? substr($this->start_time, 0, 5) : null,
            'end_time' => $this->end_time ? substr($this->end_time, 0, 5) : null,
            'location' => $this->location,
            'is_published' => $this->is_published,
            'results_count' => $this->whenCounted('results'),
        ];
    }
}
