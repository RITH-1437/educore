<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gpa_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id');
            $table->foreignId('academic_year_id');
            $table->foreignId('semester_id')->nullable();
            $table->decimal('gpa_value', 4, 3);
            $table->decimal('attempted_credits', 6, 2)->nullable();
            $table->decimal('earned_credits', 6, 2)->nullable();
            $table->decimal('grade_points', 6, 2)->nullable();
            $table->boolean('cumulative')->default(false);
            $table->timestampTz('computed_at')->useCurrent();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('student_id', 'idx_gpa_records_student');

            $table->foreign('student_id', 'fk_gpa_records_student')
                ->references('id')->on('students')->restrictOnDelete();
            $table->foreign('academic_year_id', 'fk_gpa_records_year')
                ->references('id')->on('academic_years')->restrictOnDelete();
            $table->foreign('semester_id', 'fk_gpa_records_semester')
                ->references('id')->on('semesters')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE gpa_records ADD CONSTRAINT ck_gpa_records_value CHECK (gpa_value >= 0)');
        DB::statement('CREATE UNIQUE INDEX uq_gpa_records ON gpa_records (student_id, academic_year_id, semester_id) NULLS NOT DISTINCT');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS uq_gpa_records');
        Schema::dropIfExists('gpa_records');
    }
};