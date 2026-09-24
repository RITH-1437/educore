<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id');
            $table->foreignId('room_id');
            $table->smallInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['section_id', 'day_of_week', 'start_time'], 'uq_schedule_section_slot');
            $table->unique(['room_id', 'day_of_week', 'start_time', 'end_time'], 'uq_schedule_room_slot');
            $table->index('room_id', 'idx_schedule_room');

            $table->foreign('section_id', 'fk_schedule_section')
                ->references('id')->on('sections')->restrictOnDelete();
            $table->foreign('room_id', 'fk_schedule_room')
                ->references('id')->on('rooms')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE schedule_entries ADD CONSTRAINT ck_schedule_dow CHECK (day_of_week BETWEEN 1 AND 7)');
        DB::statement('ALTER TABLE schedule_entries ADD CONSTRAINT ck_schedule_times CHECK (start_time < end_time)');
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_entries');
    }
};