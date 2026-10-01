<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\ScheduleEntry;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Raw row for test setup; rules are exercised through `TimetableService`.
 *
 * @extends Factory<ScheduleEntry>
 */
class ScheduleEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'room_id' => Room::factory(),
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:30',
        ];
    }
}
