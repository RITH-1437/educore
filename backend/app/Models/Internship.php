<?php

namespace App\Models;

use App\Models\Concerns\BelongsToDepartment;
use App\Support\DepartmentScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A student's internship application and, once approved, its record
 * (`skills/internship`). Never deleted; `notes` is an append-only log of
 * review decisions.
 *
 * draft → submitted → under_review → approved → in_progress → completed,
 * or rejected / cancelled.
 *
 * @property int $id
 * @property int $student_id
 * @property int $company_id
 * @property string $position_title
 * @property string|null $description
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property string|null $supervisor_name
 * @property string|null $supervisor_email
 * @property string|null $supervisor_phone
 * @property string $status
 * @property Carbon|null $submitted_at
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string|null $notes
 * @property-read Student $student
 * @property-read InternshipCompany $company
 */
class Internship extends Model
{
    use BelongsToDepartment;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT, self::STATUS_SUBMITTED, self::STATUS_UNDER_REVIEW, self::STATUS_APPROVED,
        self::STATUS_REJECTED, self::STATUS_IN_PROGRESS, self::STATUS_COMPLETED, self::STATUS_CANCELLED,
    ];

    /** Statuses covered by `uq_internships_active` (one per student). */
    public const ACTIVE_STATUSES = [self::STATUS_SUBMITTED, self::STATUS_UNDER_REVIEW, self::STATUS_APPROVED, self::STATUS_IN_PROGRESS];

    /** Finished — no further changes. */
    public const FINAL_STATUSES = [self::STATUS_REJECTED, self::STATUS_COMPLETED, self::STATUS_CANCELLED];

    protected $fillable = [
        'student_id', 'company_id', 'position_title', 'description', 'start_date', 'end_date',
        'supervisor_name', 'supervisor_email', 'supervisor_phone', 'status', 'submitted_at',
        'reviewed_by', 'reviewed_at', 'notes',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['status' => self::STATUS_DRAFT];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'submitted_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(InternshipCompany::class, 'company_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(InternshipReport::class)->orderBy('submitted_at')->orderBy('id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(InternshipEvaluation::class)->orderBy('evaluator_type');
    }

    /**
     * Unit ownership (`App\Support\DepartmentScope`).
     *
     * @param  Builder<self>  $query
     */
    public function scopeInDepartment(Builder $query, int $departmentId): void
    {
        $query->whereIn('student_id', DepartmentScope::studentIds($departmentId));
    }
}
