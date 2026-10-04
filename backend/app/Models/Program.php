<?php

namespace App\Models;

use App\Models\Concerns\BelongsToDepartment;
use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A degree track offered by a department (e.g. Bachelor of Computer Science).
 *
 * Deliberately *not* soft deleted: the schema gives `programs` an `is_active`
 * flag, which is the supported "archive" (`skills/program-management/SKILL.md`
 * §6). Hard deletion is only possible while nothing references the program.
 *
 * @property int $id
 * @property int $department_id
 * @property string $code
 * @property string $name
 * @property string $degree_level
 * @property int|null $duration_years
 * @property numeric-string|null $credits_required
 * @property bool $is_active
 */
class Program extends Model
{
    use BelongsToDepartment;

    /** @use HasFactory<ProgramFactory> */
    use HasFactory;

    /** Degree levels a program may declare. */
    public const DEGREE_LEVELS = ['associate', 'bachelor', 'master', 'doctorate'];

    protected $fillable = [
        'department_id',
        'code',
        'name',
        'degree_level',
        'duration_years',
        'credits_required',
        'tuition_per_credit',
        'is_active',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'duration_years' => 'integer',
            'credits_required' => 'decimal:1',
            'tuition_per_credit' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * The program's curriculum (`course_programs`), with where each course sits.
     *
     * @return BelongsToMany<Course, $this>
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_programs')
            ->withPivot(['is_required', 'suggested_semester'])
            ->withTimestamps();
    }

    /*
     * Students (`student_programs`) have no model yet — they arrive with 9.2,
     * so `ProgramService` counts them with the query builder.
     */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
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
