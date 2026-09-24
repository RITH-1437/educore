<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id');
            $table->foreignId('semester_id');
            $table->string('status', 20)->default('draft');
            $table->integer('max_enrollments')->nullable();
            $table->text('notes')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['course_id', 'semester_id'], 'uq_course_offerings');
            $table->index('semester_id', 'idx_course_offerings_semester');

            $table->foreign('course_id', 'fk_course_offerings_course')
                ->references('id')->on('courses')->restrictOnDelete();
            $table->foreign('semester_id', 'fk_course_offerings_semester')
                ->references('id')->on('semesters')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE course_offerings ADD CONSTRAINT ck_course_offerings_status CHECK (status IN (\'draft\', \'published\', \'open\', \'closed\'))');
    }

    public function down(): void
    {
        Schema::dropIfExists('course_offerings');
    }
};