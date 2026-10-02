<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * A student's internship report (initial / progress / final), optionally with
 * a private file in MinIO. Staff mark it reviewed with a comment.
 *
 * @property int $id
 * @property int $internship_id
 * @property string $report_type
 * @property string $title
 * @property string|null $summary
 * @property Carbon $submitted_at
 * @property string $status
 * @property string|null $reviewer_comment
 */
class InternshipReport extends Model
{
    public const TYPES = ['initial', 'progress', 'final'];

    protected $fillable = ['internship_id', 'report_type', 'title', 'summary', 'submitted_at', 'status', 'reviewer_comment'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function file(): MorphOne
    {
        return $this->morphOne(StoredFile::class, 'fileable');
    }
}
