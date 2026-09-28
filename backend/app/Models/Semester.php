<?php

namespace App\Models;

use App\Enums\SemesterStatus;
use Database\Factories\SemesterFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A term inside an academic year. A semester is the anchor for course
 * offerings, and therefore for enrollment, attendance and grading.
 *
 * @property int $id
 * @property int $academic_year_id
 * @property string $name
 * @property string $code
 * @property int $sequence
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property Carbon|null $enrollment_start
 * @property Carbon|null $enrollment_end
 * @property Carbon|null $exam_start
 * @property Carbon|null $exam_end
 * @property SemesterStatus $status
 */
class Semester extends Model
{
    /** @use HasFactory<SemesterFactory> */
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'name',
        'code',
        'sequence',
        'start_date',
        'end_date',
        'enrollment_start',
        'enrollment_end',
        'exam_start',
        'exam_end',
        'status',
    ];

    /**
     * Mirror the column default so a freshly created model is never missing it
     * before it is reloaded from the database.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'planned',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'enrollment_start' => 'date',
            'enrollment_end' => 'date',
            'exam_start' => 'date',
            'exam_end' => 'date',
            'status' => SemesterStatus::class,
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function scopeStatus(Builder $query, SemesterStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }
}
