<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id');
            $table->foreignId('section_id');
            $table->foreignId('academic_year_id');
            $table->foreignId('semester_id');
            $table->string('status', 20)->default('pending');
            $table->timestampTz('enrolled_at')->useCurrent();
            $table->timestampTz('dropped_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->softDeletesTz();

            $table->unique(['student_id', 'section_id'], 'uq_enrollments_student_section');
            $table->index(['academic_year_id', 'semester_id'], 'idx_enrollments_year_semester');
            $table->index(['section_id', 'status'], 'idx_enrollments_section_status');
            $table->index(['student_id', 'academic_year_id'], 'idx_enrollments_student_year');

            $table->foreign('student_id', 'fk_enrollments_student')
                ->references('id')->on('students')->restrictOnDelete();
            $table->foreign('section_id', 'fk_enrollments_section')
                ->references('id')->on('sections')->restrictOnDelete();
            $table->foreign('academic_year_id', 'fk_enrollments_year')
                ->references('id')->on('academic_years')->restrictOnDelete();
            $table->foreign('semester_id', 'fk_enrollments_semester')
                ->references('id')->on('semesters')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE enrollments ADD CONSTRAINT ck_enrollments_status CHECK (status IN (\'pending\', \'confirmed\', \'completed\', \'dropped\', \'withdrawn\'))');
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};