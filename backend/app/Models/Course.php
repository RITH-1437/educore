<?php

namespace App\Models;

use App\Models\Concerns\BelongsToDepartment;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A stable curriculum unit (code, name, credits) owned by a department.
 *
 * Not soft deleted: the schema models the lifecycle with `status`
 * (`draft` → `active` → `archived`), and hard deletion is only possible while
 * nothing references the course (`skills/course-management/SKILL.md` §12).
 *
 * @property int $id
 * @property int $department_id
 * @property string $code
 * @property string $name
 * @property numeric-string $credits
 * @property int|null $lecture_hours
 * @property int|null $lab_hours
 * @property string|null $description
 * @property string|null $course_level
 * @property string $status
 */
class Course extends Model
{
    use BelongsToDepartment;

    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_ACTIVE, self::STATUS_ARCHIVED];

    /** Statuses an editor may set directly; archiving has its own action. */
    public const EDITABLE_STATUSES = [self::STATUS_DRAFT, self::STATUS_ACTIVE];

    public const LEVELS = ['introductory', 'intermediate', 'advanced', 'graduate'];

    protected $fillable = [
        'department_id',
        'code',
        'name',
        'credits',
        'lecture_hours',
        'lab_hours',
        'description',
        'course_level',
        'status',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    protected function casts(): array
    {
        return [
            'credits' => 'decimal:2',
            'lecture_hours' => 'integer',
            'lab_hours' => 'integer',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Programs whose curriculum includes this course (`course_programs`).
     *
     * @return BelongsToMany<Program, $this>
     */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'course_programs')
            ->withPivot(['is_required', 'suggested_semester'])
            ->withTimestamps();
    }

    /**
     * Courses that must be completed first.
     *
     * @return BelongsToMany<Course, $this>
     */
    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'course_prerequisites', 'course_id', 'prerequisite_course_id')
            ->withPivot('is_strict')
            ->withTimestamps();
    }

    /**
     * Courses that list this one as a prerequisite.
     *
     * @return BelongsToMany<Course, $this>
     */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'course_prerequisites', 'prerequisite_course_id', 'course_id')
            ->withPivot('is_strict')
            ->withTimestamps();
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(CourseOffering::class);
    }

    public function gradingConfig(): HasOne
    {
        return $this->hasOne(CourseGradingConfig::class);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if ($search === null || $search === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('code', 'ilike', "%{$search}%")
                ->orWhere('name', 'ilike', "%{$search}%");
        });
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * Unit ownership (`App\Support\DepartmentScope`).
     *
     * @param  Builder<self>  $query
     */
    public function scopeInDepartment(Builder $query, int $departmentId): void
    {
        $query->where('department_id', $departmentId);
    }
}
