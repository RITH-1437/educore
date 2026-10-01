<?php

namespace App\Http\Resources;

use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `my_submission` is set by the controller for students; staff get counts.
 *
 * @mixin Assignment
 */
class AssignmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'section_id' => $this->section_id,
            'title' => $this->title,
            'description' => $this->description,
            'instructions' => $this->instructions,
            'max_score' => (float) $this->max_score,
            'due_at' => $this->due_at?->toISOString(),
            'past_due' => $this->isPastDue(),
            'assignment_type' => $this->assignment_type,
            'weight_override' => $this->weight_override === null ? null : (float) $this->weight_override,
            'is_published' => $this->is_published,
            'published_at' => $this->published_at?->toISOString(),
            'submissions_count' => $this->whenCounted('submissions'),
            'graded_count' => $this->whenHas('graded_count'),
            'my_submission' => $this->when($this->resource->relationLoaded('mySubmission'), fn () => $this->mySubmission ? (new SubmissionResource($this->mySubmission->load('file')))->resolve($request) : null),
        ];
    }
}
