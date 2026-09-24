<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id');
            $table->string('name', 50);
            $table->string('code', 20);
            $table->smallInteger('sequence');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('enrollment_start')->nullable();
            $table->date('enrollment_end')->nullable();
            $table->date('exam_start')->nullable();
            $table->date('exam_end')->nullable();
            $table->string('status', 20)->default('planned');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['academic_year_id', 'sequence'], 'uq_semesters_year_sequence');
            $table->index('academic_year_id', 'idx_semesters_academic_year');

            $table->foreign('academic_year_id', 'fk_semesters_year')
                ->references('id')->on('academic_years')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE semesters ADD CONSTRAINT ck_semesters_status CHECK (status IN (\'planned\', \'open\', \'closed\', \'completed\'))');
        DB::statement('ALTER TABLE semesters ADD CONSTRAINT ck_semesters_range CHECK (start_date IS NULL OR end_date IS NULL OR start_date <= end_date)');
    }

    public function down(): void
    {
        Schema::dropIfExists('semesters');
    }
};