<?php

namespace App\Models;

use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A physical room (`skills/timetable/SKILL.md` §2). Retired with `is_active`;
 * deletion is only possible while nothing is scheduled in it.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $building
 * @property string|null $floor
 * @property int $capacity
 * @property string $room_type
 * @property bool $is_active
 */
class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory;

    public const TYPES = ['lecture', 'lab', 'seminar', 'other'];

    protected $fillable = ['code', 'name', 'building', 'floor', 'capacity', 'room_type', 'is_active'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['room_type' => 'lecture', 'is_active' => true];

    protected function casts(): array
    {
        return ['capacity' => 'integer', 'is_active' => 'boolean'];
    }

    public function scheduleEntries(): HasMany
    {
        return $this->hasMany(ScheduleEntry::class);
    }
}
