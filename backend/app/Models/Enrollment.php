<?php

namespace App\Models;

use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A student's registration in a section for a semester.
 *
 * Never hard-deleted by the application: dropping keeps the row with status
 * `dropped` or `withdrawn` (`skills/enrollment/SKILL.md` §6, §12).
 *
 * @property int $id
 * @property int $student_id
 * @property int $section_id
 * @property int $academic_year_id
 * @property int $semester_id
 * @property string $status
 * @property Carbon $enrolled_at
 * @property Carbon|null $dropped_at
 */
class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory;

    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_DROPPED = 'dropped';

    public const STATUS_WITHDRAWN = 'withdrawn';

    public const STATUSES = [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_COMPLETED, self::STATUS_DROPPED, self::STATUS_WITHDRAWN];

    /** Statuses that hold a seat and count toward the credit limit. */
    public const OPEN_STATUSES = [self::STATUS_PENDING, self::STATUS_CONFIRMED];

    protected $fillable = ['student_id', 'section_id', 'academic_year_id', 'semester_id', 'status', 'enrolled_at', 'dropped_at'];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
            'dropped_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }
}
