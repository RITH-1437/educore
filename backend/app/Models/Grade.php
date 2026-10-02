<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * The final course grade of one enrollment (unique `enrollment_id`).
 *
 * Workflow: `draft` (computed by the lecturer) → `submitted` (lecturer) →
 * `approved` (University Admin / Super Admin). Only approved grades count
 * toward GPA, prerequisites and the student's record; a manager may return a
 * submitted or approved grade to draft. A manager may then finalize approved
 * grades (locked: no return to draft); only Super Admin reopens them, with a
 * reason. `finalized` counts like approved (`docs/20_Grades-and-GPA-Report.md`).
 *
 * @property int $id
 * @property int $enrollment_id
 * @property string|null $letter_grade
 * @property numeric-string|null $grade_point
 * @property numeric-string|null $total_score
 * @property string $status
 * @property int|null $graded_by
 * @property Carbon|null $submitted_at
 * @property Carbon|null $approved_at
 * @property string|null $remarks
 * @property-read Enrollment $enrollment
 */
class Grade extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_FINALIZED = 'finalized';

    /** Statuses that count toward GPA, prerequisites and the student's record. */
    public const FINAL_STATUSES = [self::STATUS_APPROVED, self::STATUS_FINALIZED];

    protected $fillable = [
        'enrollment_id', 'letter_grade', 'grade_point', 'total_score', 'status',
        'graded_by', 'submitted_at', 'approved_at', 'remarks',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['status' => self::STATUS_DRAFT];

    protected function casts(): array
    {
        return [
            'grade_point' => 'decimal:2',
            'total_score' => 'decimal:2',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
