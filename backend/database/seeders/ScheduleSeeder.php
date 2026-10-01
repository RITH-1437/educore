<?php

namespace Database\Seeders;

use App\Exceptions\BusinessRuleException;
use App\Models\Room;
use App\Models\Section;
use App\Services\TimetableService;
use Illuminate\Database\Seeder;
use Illuminate\Validation\ValidationException;

/**
 * Gives every seeded open section two weekly meetings, through
 * `TimetableService` so no seeded slot breaks a conflict rule. Each section
 * gets its own time band, so there are no clashes by construction; anything
 * refused (including re-runs) is skipped.
 */
class ScheduleSeeder extends Seeder
{
    /** Time bands, one per section in seeding order. */
    private const BANDS = [['07:30', '09:00'], ['09:15', '10:45'], ['11:00', '12:30'], ['13:30', '15:00'], ['15:15', '16:45'], ['17:00', '18:30']];

    public function run(TimetableService $timetable): void
    {
        $rooms = Room::query()->where('is_active', true)->orderByDesc('capacity')->get();
        $sections = Section::query()->with('scheduleEntries', 'offering.semester')->whereIn('status', ['open', 'active'])->orderBy('id')->get();

        if ($rooms->isEmpty()) {
            return;
        }

        foreach ($sections->values() as $index => $section) {
            if ($section->scheduleEntries->isNotEmpty()) {
                continue;
            }

            [$start, $end] = self::BANDS[$index % count(self::BANDS)];
            $days = $index < count(self::BANDS) ? [1, 3] : [2, 4]; // Mon/Wed, then Tue/Thu
            $room = $rooms->first(fn (Room $r) => $r->capacity >= $section->capacity) ?? $rooms->first();

            foreach ($days as $day) {
                try {
                    $timetable->addEntry($section, ['room_id' => $room->id, 'day_of_week' => $day, 'start_time' => $start, 'end_time' => $end]);
                } catch (BusinessRuleException|ValidationException) {
                    // Refused by a conflict rule: skip.
                }
            }
        }
    }
}
