<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_prerequisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id');
            $table->foreignId('prerequisite_course_id');
            $table->boolean('is_strict')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['course_id', 'prerequisite_course_id'], 'uq_course_prerequisites');
            $table->index('prerequisite_course_id', 'idx_course_prerequisites_prereq');

            $table->foreign('course_id', 'fk_course_prerequisites_course')
                ->references('id')->on('courses')->cascadeOnDelete();
            $table->foreign('prerequisite_course_id', 'fk_course_prerequisites_prereq')
                ->references('id')->on('courses')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE course_prerequisites ADD CONSTRAINT ck_course_prerequisites_not_self CHECK (course_id <> prerequisite_course_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('course_prerequisites');
    }
};