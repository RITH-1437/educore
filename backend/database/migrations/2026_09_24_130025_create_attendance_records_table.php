<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_session_id');
            $table->foreignId('enrollment_id');
            $table->string('status', 20);
            $table->text('remarks')->nullable();
            $table->foreignId('marked_by')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['attendance_session_id', 'enrollment_id'], 'uq_attendance_records_session_enrollment');
            $table->index('enrollment_id', 'idx_attendance_records_enrollment');

            $table->foreign('attendance_session_id', 'fk_attendance_records_session')
                ->references('id')->on('attendance_sessions')->restrictOnDelete();
            $table->foreign('enrollment_id', 'fk_attendance_records_enrollment')
                ->references('id')->on('enrollments')->restrictOnDelete();
            $table->foreign('marked_by', 'fk_attendance_records_marker')
                ->references('id')->on('users')->nullOnDelete();
        });

        DB::statement('ALTER TABLE attendance_records ADD CONSTRAINT ck_attendance_records_status CHECK (status IN (\'present\', \'absent\', \'late\', \'excused\'))');
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};