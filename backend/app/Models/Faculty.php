<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFaculty;
use Database\Factories\FacultyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A faculty (e.g. Faculty of Engineering) inside the university.
 *
 * Soft deleted: once programs, courses and lecturers hang off a faculty,
 * history matters and the unit is archived rather than removed
 * (see `skills/database/SKILL.md` and `skills/faculty-department/SKILL.md` §4).
 *
 * @property int $id
 * @property int $university_id
 * @property string $code
 * @property string $name
 * @property string|null $dean_name
 * @property string|null $description
 * @property bool $is_active
 */
class Faculty extends Model
{
    use BelongsToFaculty;

    /** @use HasFactory<FacultyFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'university_id',
        'code',
        'name',
        'dean_name',
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

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class)->orderBy('name');
    }

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
     * Unit ownership (`App\Support\FacultyScope`).
     *
     * @param  Builder<self>  $query
     */
    public function scopeInFaculty(Builder $query, int $facultyId): void
    {
        $query->whereKey($facultyId);
    }
}
