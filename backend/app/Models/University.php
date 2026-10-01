<?php

namespace App\Models;

use Database\Factories\UniversityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The university that owns the academic structure.
 *
 * Single-tenant reference data: it is never soft deleted (see
 * `skills/database/SKILL.md`), it carries an `is_current` flag instead, and
 * exactly one row may hold it (enforced by `UniversityService`).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $short_name
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $logo_key
 * @property string|null $website
 * @property bool $is_current
 */
class University extends Model
{
    /** @use HasFactory<UniversityFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'short_name',
        'address',
        'phone',
        'email',
        'logo_key',
        'website',
        'is_current',
    ];

    /**
     * Mirror the column default so a freshly created model is never missing
     * the flag before it is reloaded from the database.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_current' => false,
    ];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
        ];
    }

    public function faculties(): HasMany
    {
        return $this->hasMany(Faculty::class);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    public function isCurrent(): bool
    {
        return $this->is_current;
    }
}
