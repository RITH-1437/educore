<?php

namespace Database\Seeders;

use App\Exceptions\BusinessRuleException;
use App\Models\Section;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Database\Seeder;
use Illuminate\Validation\ValidationException;

/**
 * Records attendance for the first scheduled meetings of each open section,
 * through `AttendanceService` (date policy, membership). Statuses follow a
 * fixed pattern, so the data is deterministic; existing sessions are simply
 * re-saved with the same values (idempotent).
 */
class AttendanceSeeder extends Seeder
{
    private const SESSIONS_PER_SECTION = 6;

    private const PATTERN = ['present', 'present', 'present', 'late', 'present', 'absent', 'present', 'excused'];

    public function run(AttendanceService $attendance): void
    {
        $sections = Section::query()->with('lecturers.user', 'scheduleEntries', 'offering.semester')->whereIn('status', ['open', 'active'])->get();

        foreach ($sections as $section) {
            $marker = $section->lecturers->first()?->user ?? User::query()->orderBy('id')->first();
            $roster = $section->enrollments()->whereIn('status', ['pending', 'confirmed', 'completed'])->orderBy('id')->pluck('id')->all();

            if ($marker === null || $roster === []) {
                continue;
            }

            $dates = $attendance->expectedDates($section)->reverse()->take(self::SESSIONS_PER_SECTION);

            foreach ($dates->values() as $s => $date) {
                $records = [];

                foreach ($roster as $i => $enrollmentId) {
                    $records[] = ['enrollment_id' => $enrollmentId, 'status' => self::PATTERN[($i + $s) % count(self::PATTERN)]];
                }

                try {
                    $attendance->record($section, $date['date'], $records, $marker, 'Week '.($s + 1));
                } catch (BusinessRuleException|ValidationException) {
                    // Refused by the date policy: skip.
                }
            }
        }
    }
}
