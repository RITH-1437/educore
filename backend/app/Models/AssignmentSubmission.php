<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * A student's single submission for an assignment (unique per assignment and
 * enrollment). The work itself is a private file in MinIO (`files`).
 *
 * @property int $id
 * @property int $assignment_id
 * @property int $enrollment_id
 * @property Carbon $submitted_at
 * @property string $status
 * @property numeric-string|null $score
 * @property string|null $feedback
 * @property int|null $graded_by
 * @property Carbon|null $graded_at
 */
class AssignmentSubmission extends Model
{
    public const STATUSES = ['submitted', 'late', 'graded', 'returned'];

    protected $fillable = ['assignment_id', 'enrollment_id', 'submitted_at', 'status', 'score', 'feedback', 'graded_by', 'graded_at'];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'graded_at' => 'datetime',
            'score' => 'decimal:2',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function file(): MorphOne
    {
        return $this->morphOne(StoredFile::class, 'fileable');
    }
}
