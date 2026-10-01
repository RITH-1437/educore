<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `uq_schedule_room_slot (room_id, day_of_week, start_time, end_time)` made a
     * room's weekly slot unique **across all semesters**: schedule entries are
     * recurring weekly slots with no semester column, so a room could never be
     * reused at the same time in a later semester.
     *
     * Room conflicts are per semester (`skills/timetable/SKILL.md` §4) and also
     * cover partial overlaps, which this constraint never did. They are enforced
     * by `TimetableService` under a row lock on the room. The section-level
     * constraint (`uq_schedule_section_slot`) stays: a section belongs to one
     * semester.
     */
    public function up(): void
    {
        Schema::table('schedule_entries', function (Blueprint $table) {
            $table->dropUnique('uq_schedule_room_slot');
        });
    }

    public function down(): void
    {
        Schema::table('schedule_entries', function (Blueprint $table) {
            $table->unique(['room_id', 'day_of_week', 'start_time', 'end_time'], 'uq_schedule_room_slot');
        });
    }
};
