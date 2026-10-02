<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFaculty;
use App\Support\FacultyScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * A student's request for an official document.
 *
 * `pending → approved → generated`, or `pending → rejected` (with a reason).
 * Rows are never deleted (`skills/documents` §12).
 *
 * @property int $id
 * @property int $student_id
 * @property int $document_type_id
 * @property int|null $academic_year_id
 * @property int|null $semester_id
 * @property string|null $reason
 * @property string $status
 * @property Carbon $submitted_at
 * @property int|null $processed_by
 * @property Carbon|null $processed_at
 * @property string|null $rejection_reason
 * @property string|null $notes
 * @property-read Student $student
 * @property-read DocumentType $type
 * @property-read Semester|null $semester
 * @property-read Document|null $document
 */
class DocumentRequest extends Model
{
    use BelongsToFaculty;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_GENERATED = 'generated';

    public const STATUSES = [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_GENERATED];

    protected $fillable = [
        'student_id', 'document_type_id', 'academic_year_id', 'semester_id', 'reason', 'status',
        'submitted_at', 'processed_by', 'processed_at', 'rejection_reason', 'notes',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['status' => self::STATUS_PENDING];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'processed_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function document(): HasOne
    {
        return $this->hasOne(Document::class);
    }

    /**
     * Unit ownership (`App\Support\FacultyScope`).
     *
     * @param  Builder<self>  $query
     */
    public function scopeInFaculty(Builder $query, int $facultyId): void
    {
        $query->whereIn('student_id', FacultyScope::studentIds($facultyId));
    }
}
