<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One dated class meeting of a section (unique per section and date).
 * `cancelled` sessions keep their records but leave the attendance rate.
 *
 * @property int $id
 * @property int $section_id
 * @property Carbon $session_date
 * @property string|null $start_time
 * @property string|null $end_time
 * @property string|null $topic
 * @property string $status
 * @property int|null $recorded_by
 */
class AttendanceSession extends Model
{
    public const STATUSES = ['scheduled', 'held', 'cancelled'];

    protected $fillable = ['section_id', 'session_date', 'start_time', 'end_time', 'topic', 'status', 'recorded_by'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['status' => 'held'];

    protected function casts(): array
    {
        return ['session_date' => 'date'];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
