<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id');
            $table->string('code', 20);
            $table->string('name', 255);
            $table->decimal('credits', 4, 2);
            $table->smallInteger('lecture_hours')->nullable();
            $table->smallInteger('lab_hours')->nullable();
            $table->text('description')->nullable();
            $table->string('course_level', 20)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique('code', 'uq_courses_code');
            $table->index('department_id', 'idx_courses_department');
            $table->index('status', 'idx_courses_status');

            $table->foreign('department_id', 'fk_courses_department')
                ->references('id')->on('departments')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE courses ADD CONSTRAINT ck_courses_credits CHECK (credits > 0)');
        DB::statement('ALTER TABLE courses ADD CONSTRAINT ck_courses_status CHECK (status IN (\'draft\', \'active\', \'archived\'))');
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};