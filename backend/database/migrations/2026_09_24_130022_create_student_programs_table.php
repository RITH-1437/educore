<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id');
            $table->foreignId('program_id');
            $table->date('started_on');
            $table->date('ended_on')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['student_id', 'program_id', 'started_on'], 'uq_student_programs');
            $table->index('program_id', 'idx_student_programs_program');

            $table->foreign('student_id', 'fk_student_programs_student')
                ->references('id')->on('students')->restrictOnDelete();
            $table->foreign('program_id', 'fk_student_programs_program')
                ->references('id')->on('programs')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE student_programs ADD CONSTRAINT ck_student_programs_status CHECK (status IN (\'active\', \'completed\', \'withdrawn\', \'transferred\'))');
        DB::statement('ALTER TABLE student_programs ADD CONSTRAINT ck_student_programs_dates CHECK (ended_on IS NULL OR ended_on >= started_on)');
        DB::statement('CREATE UNIQUE INDEX uq_student_programs_active ON student_programs (student_id) WHERE status = \'active\'');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS uq_student_programs_active');
        Schema::dropIfExists('student_programs');
    }
};