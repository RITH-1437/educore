<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id');
            $table->date('session_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('topic', 255)->nullable();
            $table->string('status', 20)->default('held');
            $table->foreignId('recorded_by')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['section_id', 'session_date'], 'uq_attendance_sessions_section_date');
            $table->index('recorded_by', 'idx_attendance_sessions_recorder');

            $table->foreign('section_id', 'fk_attendance_sessions_section')
                ->references('id')->on('sections')->restrictOnDelete();
            $table->foreign('recorded_by', 'fk_attendance_sessions_recorder')
                ->references('id')->on('users')->nullOnDelete();
        });

        DB::statement('ALTER TABLE attendance_sessions ADD CONSTRAINT ck_attendance_sessions_status CHECK (status IN (\'scheduled\', \'held\', \'cancelled\'))');
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};