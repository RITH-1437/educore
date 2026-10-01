<?php

namespace App\Http\Resources;

use App\Models\AssignmentSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Exposes file metadata and an authorized download path — never the storage
 * key or a direct MinIO URL.
 *
 * @mixin AssignmentSubmission
 */
class SubmissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $student = $this->relationLoaded('enrollment') && $this->enrollment->relationLoaded('student') ? $this->enrollment->student : null;

        return [
            'id' => $this->id,
            'assignment_id' => $this->assignment_id,
            'enrollment_id' => $this->enrollment_id,
            'student' => $student ? ['id' => $student->id, 'student_number' => $student->student_number, 'full_name' => $student->fullName()] : null,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'status' => $this->status,
            'score' => $this->score === null ? null : (float) $this->score,
            'feedback' => $this->feedback,
            'graded_at' => $this->graded_at?->toISOString(),
            'file' => $this->whenLoaded('file', fn () => $this->file ? [
                'name' => $this->file->original_name,
                'mime_type' => $this->file->mime_type,
                'size' => $this->file->size,
                'download_url' => "/submissions/{$this->id}/file",
            ] : null),
        ];
    }
}
