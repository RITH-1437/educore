<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id');
            $table->foreignId('program_id');
            $table->boolean('is_required')->default(false);
            $table->smallInteger('suggested_semester')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['course_id', 'program_id'], 'uq_course_programs');
            $table->index('program_id', 'idx_course_programs_program');

            $table->foreign('course_id', 'fk_course_programs_course')
                ->references('id')->on('courses')->cascadeOnDelete();
            $table->foreign('program_id', 'fk_course_programs_program')
                ->references('id')->on('programs')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_programs');
    }
};