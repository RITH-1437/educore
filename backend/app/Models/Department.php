<?php

namespace App\Models;

use App\Models\Concerns\BelongsToDepartment;
use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A department of the university — the only unit between the university and
 * its programs (`skills/faculty-department/SKILL.md`; the faculty level was
 * removed in report 39).
 *
 * Soft deleted: programs, courses and lecturers reference it, so units are
 * archived instead of removed.
 *
 * @property int $id
 * @property int $university_id
 * @property string $code
 * @property string $name
 * @property string|null $head_name
 * @property string|null $description
 * @property bool $is_active
 */
class Department extends Model
{
    use BelongsToDepartment;

    /** @use HasFactory<DepartmentFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'university_id',
        'code',
        'name',
        'head_name',
        'description',
        'is_active',
    ];

    /**
     * Mirror the column default so a freshly created model is never missing
     * `is_active` before it is reloaded from the database.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    /*
     * `courses` and `lecturers` hang off this table in the schema but have no
     * models yet (9.7 / 9.3). The delete guards in `DepartmentService` count
     * child rows with the query builder, so no unbuilt module is depended upon.
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

    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Unit ownership (`App\Support\DepartmentScope`): a department owns itself.
     *
     * @param  Builder<self>  $query
     */
    public function scopeInDepartment(Builder $query, int $departmentId): void
    {
        $query->whereKey($departmentId);
    }
}
