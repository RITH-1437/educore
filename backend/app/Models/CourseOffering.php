<?php

namespace App\Models;

use Database\Factories\CourseOfferingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A course taught in a specific semester (`skills/academic-domain`: course
 * offering = course × semester). Unique per `(course_id, semester_id)`.
 *
 * @property int $id
 * @property int $course_id
 * @property int $semester_id
 * @property string $status
 * @property int|null $max_enrollments
 * @property string|null $notes
 */
class CourseOffering extends Model
{
    /** @use HasFactory<CourseOfferingFactory> */
    use HasFactory;

    public const STATUSES = ['draft', 'published', 'open', 'closed'];

    protected $fillable = ['course_id', 'semester_id', 'status', 'max_enrollments', 'notes'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['status' => 'draft'];

    protected function casts(): array
    {
        return ['max_enrollments' => 'integer'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('code');
    }
}
