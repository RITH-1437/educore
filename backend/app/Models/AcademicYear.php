<?php

namespace App\Models;

use App\Enums\AcademicYearStatus;
use Database\Factories\AcademicYearFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * An academic year (e.g. 2026-2027) owns the semesters of that calendar span.
 *
 * Reference data: it is never soft deleted (see `skills/database/SKILL.md`),
 * the delete guard lives in `AcademicYearService`.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property AcademicYearStatus $status
 * @property bool $is_current
 */
class AcademicYear extends Model
{
    /** @use HasFactory<AcademicYearFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'start_date',
        'end_date',
        'status',
        'is_current',
    ];

    /**
     * Mirror the column defaults so a freshly created model is never missing
     * them before it is reloaded from the database.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'planned',
        'is_current' => false,
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => AcademicYearStatus::class,
            'is_current' => 'boolean',
        ];
    }

    public function semesters(): HasMany
    {
        return $this->hasMany(Semester::class)->orderBy('sequence');
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    public function scopeStatus(Builder $query, AcademicYearStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    public function isCurrent(): bool
    {
        return $this->is_current;
    }
}
