<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One band of a named grading scale (percentage range → letter + grade point).
 *
 * A scale is the set of rows sharing a `name`; the rows of the scale in use
 * carry `is_active = true` (`skills/grading-gpa/SKILL.md` §4 — configurable,
 * never hard-coded). Bands are maintained by `GradingService::saveScale()`,
 * which derives `max_percentage` so a scale never has gaps or overlaps.
 *
 * @property int $id
 * @property string $name
 * @property string $grade
 * @property numeric-string $min_percentage
 * @property numeric-string $max_percentage
 * @property numeric-string $grade_point
 * @property bool $is_pass
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class GradingScale extends Model
{
    /** Name given to the scale when none exists yet. */
    public const DEFAULT_NAME = 'Standard';

    protected $fillable = ['name', 'grade', 'min_percentage', 'max_percentage', 'grade_point', 'is_pass', 'is_active'];

    protected function casts(): array
    {
        return [
            'min_percentage' => 'decimal:2',
            'max_percentage' => 'decimal:2',
            'grade_point' => 'decimal:2',
            'is_pass' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
