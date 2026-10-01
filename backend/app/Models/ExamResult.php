<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One student's result for an exam (unique per exam and enrollment). A null
 * score means "recorded as not sat / pending".
 *
 * @property int $id
 * @property int $exam_id
 * @property int $enrollment_id
 * @property numeric-string|null $score
 * @property string|null $remarks
 * @property int|null $recorded_by
 */
class ExamResult extends Model
{
    protected $fillable = ['exam_id', 'enrollment_id', 'score', 'remarks', 'recorded_by'];

    protected function casts(): array
    {
        return ['score' => 'decimal:2'];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
}
