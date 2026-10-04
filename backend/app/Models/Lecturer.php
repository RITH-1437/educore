<?php

namespace App\Models;

use App\Models\Concerns\BelongsToDepartment;
use Database\Factories\LecturerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A lecturer profile. The login identity lives on `users` (role `lecturer`);
 * this row holds the academic/employment profile and the home department
 * (`skills/lecturer-management/SKILL.md` §2).
 *
 * Not soft deleted — the schema has no `deleted_at`. `is_active` is the
 * supported way to retire a lecturer and is mirrored onto the login account.
 *
 * @property int $id
 * @property int $user_id
 * @property string $staff_number
 * @property string $first_name
 * @property string $last_name
 * @property string|null $title
 * @property int $department_id
 * @property string|null $position
 * @property string|null $specialization
 * @property string $employment_type
 * @property bool $is_active
 */
class Lecturer extends Model
{
    use BelongsToDepartment;

    /** @use HasFactory<LecturerFactory> */
    use HasFactory;

    public const EMPLOYMENT_TYPES = ['full_time', 'part_time', 'contract', 'visiting'];

    protected $fillable = [
        'user_id',
        'staff_number',
        'first_name',
        'last_name',
        'title',
        'department_id',
        'position',
        'specialization',
        'employment_type',
        'is_active',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'employment_type' => 'full_time',
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Sections this lecturer teaches (`section_lecturers`, with their role).
     *
     * @return BelongsToMany<Section, $this>
     */
    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class, 'section_lecturers')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function fullName(): string
    {
        return trim(($this->title ? $this->title.' ' : '').$this->first_name.' '.$this->last_name);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if ($search === null || $search === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('staff_number', 'ilike', "%{$search}%")
                ->orWhere('first_name', 'ilike', "%{$search}%")
                ->orWhere('last_name', 'ilike', "%{$search}%")
                ->orWhere('specialization', 'ilike', "%{$search}%")
                ->orWhereHas('user', fn (Builder $user) => $user->where('email', 'ilike', "%{$search}%"));
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
