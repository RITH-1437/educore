<?php

namespace App\Models;

use Database\Factories\ScheduleEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A recurring weekly meeting of a section: ISO day of week (1 = Monday …
 * 7 = Sunday), start/end time and room (`skills/timetable/SKILL.md` §2).
 *
 * @property int $id
 * @property int $section_id
 * @property int $room_id
 * @property int $day_of_week
 * @property string $start_time
 * @property string $end_time
 */
class ScheduleEntry extends Model
{
    /** @use HasFactory<ScheduleEntryFactory> */
    use HasFactory;

    public const DAYS = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

    protected $fillable = ['section_id', 'room_id', 'day_of_week', 'start_time', 'end_time'];

    protected function casts(): array
    {
        return ['day_of_week' => 'integer'];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /** `HH:MM` regardless of how the database returns TIME. */
    public function startsAt(): string
    {
        return substr((string) $this->start_time, 0, 5);
    }

    public function endsAt(): string
    {
        return substr((string) $this->end_time, 0, 5);
    }
}
