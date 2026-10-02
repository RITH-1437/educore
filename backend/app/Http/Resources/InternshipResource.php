<?php

namespace App\Http\Resources;

use App\Models\Internship;
use App\Models\InternshipEvaluation;
use App\Models\InternshipReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An internship with company, student, and (when loaded) reports and
 * evaluations.
 *
 * @mixin Internship
 */
class InternshipResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'position_title' => $this->position_title,
            'description' => $this->description,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'supervisor_name' => $this->supervisor_name,
            'supervisor_email' => $this->supervisor_email,
            'supervisor_phone' => $this->supervisor_phone,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'notes' => $this->notes,
            'company' => $this->whenLoaded('company', fn () => ['id' => $this->company->id, 'name' => $this->company->name, 'industry' => $this->company->industry]),
            'student' => $this->whenLoaded('student', fn () => ['id' => $this->student->id, 'student_number' => $this->student->student_number, 'full_name' => $this->student->fullName()]),
            'reports' => $this->whenLoaded('reports', fn () => $this->reports->map(fn (InternshipReport $report) => [
                'id' => $report->id,
                'report_type' => $report->report_type,
                'title' => $report->title,
                'summary' => $report->summary,
                'status' => $report->status,
                'reviewer_comment' => $report->reviewer_comment,
                'submitted_at' => $report->submitted_at->toIso8601String(),
                'file' => $report->relationLoaded('file') && $report->file ? ['name' => $report->file->original_name, 'size' => $report->file->size] : null,
            ])->values()),
            'evaluations' => $this->whenLoaded('evaluations', fn () => $this->evaluations->map(fn (InternshipEvaluation $evaluation) => [
                'id' => $evaluation->id,
                'evaluator_type' => $evaluation->evaluator_type,
                'evaluator_name' => $evaluation->evaluator_name,
                'score' => $evaluation->score === null ? null : (float) $evaluation->score,
                'rating' => $evaluation->rating,
                'comments' => $evaluation->comments,
                'evaluated_at' => $evaluation->evaluated_at?->toIso8601String(),
            ])->values()),
        ];
    }
}
