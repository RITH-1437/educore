<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\CourseGradingConfigFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Component-weight configuration for a course's grading breakdown.
 *
 * One record per course (unique `course_id`). Weights must be non-negative and
 * must sum to exactly 100 (enforced by a DB CHECK constraint and validated in
 * the service layer — see `skills/grading-gpa/SKILL.md` §4).
 *
 * Default weights: attendance 10%, assignment 25%, midterm 20%, final 40%,
 * practical 5%.
 *
 * @property int $id
 * @property int $course_id
 * @property numeric-string $attendance_weight
 * @property numeric-string $assignment_weight
 * @property numeric-string $midterm_weight
 * @property numeric-string $final_weight
 * @property numeric-string $practical_weight
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Course $course
 */
class CourseGradingConfig extends Model
{
    /** @use HasFactory<CourseGradingConfigFactory> */
    use HasFactory;

    protected $fillable = [
        'course_id',
        'attendance_weight',
        'assignment_weight',
        'midterm_weight',
        'final_weight',
        'practical_weight',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'attendance_weight' => 10,
        'assignment_weight' => 25,
        'midterm_weight' => 20,
        'final_weight' => 40,
        'practical_weight' => 5,
    ];

    protected function casts(): array
    {
        return [
            'attendance_weight' => 'decimal:2',
            'assignment_weight' => 'decimal:2',
            'midterm_weight' => 'decimal:2',
            'final_weight' => 'decimal:2',
            'practical_weight' => 'decimal:2',
        ];
    }

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    // -----------------------------------------------------------------------
    // Domain helpers
    // -----------------------------------------------------------------------

    /**
     * Return the sum of all component weights.
     *
     * The DB CHECK constraint enforces sum = 100; this helper can be used in
     * the service layer before persisting (see §4 of grading-gpa skill).
     */
    public function weightSum(): float
    {
        return (float) $this->attendance_weight
            + (float) $this->assignment_weight
            + (float) $this->midterm_weight
            + (float) $this->final_weight
            + (float) $this->practical_weight;
    }

    /**
     * Compute a weighted total score from raw component scores (0–100 scale).
     *
     * @param  array{attendance: float|int, assignment: float|int, midterm: float|int, final: float|int, practical: float|int}  $scores
     */
    public function computeTotalScore(array $scores): float
    {
        return ((float) $this->attendance_weight * $scores['attendance']
            + (float) $this->assignment_weight * $scores['assignment']
            + (float) $this->midterm_weight * $scores['midterm']
            + (float) $this->final_weight * $scores['final']
            + (float) $this->practical_weight * $scores['practical'])
            / 100;
    }
}
