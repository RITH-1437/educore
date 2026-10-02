<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A computed GPA snapshot — never a source of truth.
 *
 * Rows are rebuilt by `GpaService::recalculate()` from approved grades every
 * time a grade is approved / returned or a course's credits change:
 * one row per semester (`cumulative = false`) and one cumulative row per
 * academic year (`semester_id = null`, `cumulative = true`, "as of the end of
 * that year").
 *
 * @property int $id
 * @property int $student_id
 * @property int $academic_year_id
 * @property int|null $semester_id
 * @property numeric-string $gpa_value
 * @property numeric-string|null $attempted_credits
 * @property numeric-string|null $earned_credits
 * @property numeric-string|null $grade_points
 * @property bool $cumulative
 * @property Carbon $computed_at
 */
class GpaRecord extends Model
{
    protected $fillable = [
        'student_id', 'academic_year_id', 'semester_id', 'gpa_value',
        'attempted_credits', 'earned_credits', 'grade_points', 'cumulative', 'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'gpa_value' => 'decimal:3',
            'attempted_credits' => 'decimal:2',
            'earned_credits' => 'decimal:2',
            'grade_points' => 'decimal:2',
            'cumulative' => 'boolean',
            'computed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }
}
