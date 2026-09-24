<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_grading_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id');
            $table->decimal('attendance_weight', 5, 2)->default(10);
            $table->decimal('assignment_weight', 5, 2)->default(25);
            $table->decimal('midterm_weight', 5, 2)->default(20);
            $table->decimal('final_weight', 5, 2)->default(40);
            $table->decimal('practical_weight', 5, 2)->default(5);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique('course_id', 'uq_course_grading_configs');

            $table->foreign('course_id', 'fk_course_grading_configs_course')
                ->references('id')->on('courses')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE course_grading_configs ADD CONSTRAINT ck_course_grading_configs_nonneg CHECK (attendance_weight >= 0 AND assignment_weight >= 0 AND midterm_weight >= 0 AND final_weight >= 0 AND practical_weight >= 0)');
        DB::statement('ALTER TABLE course_grading_configs ADD CONSTRAINT ck_course_grading_configs_sum CHECK (attendance_weight + assignment_weight + midterm_weight + final_weight + practical_weight = 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('course_grading_configs');
    }
};