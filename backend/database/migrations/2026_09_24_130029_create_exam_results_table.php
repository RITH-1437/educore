<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id');
            $table->foreignId('enrollment_id');
            $table->decimal('score', 6, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('recorded_by')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['exam_id', 'enrollment_id'], 'uq_exam_results');
            $table->index('enrollment_id', 'idx_exam_results_enrollment');

            $table->foreign('exam_id', 'fk_exam_results_exam')
                ->references('id')->on('exams')->restrictOnDelete();
            $table->foreign('enrollment_id', 'fk_exam_results_enrollment')
                ->references('id')->on('enrollments')->restrictOnDelete();
            $table->foreign('recorded_by', 'fk_exam_results_recorder')
                ->references('id')->on('users')->nullOnDelete();
        });

        DB::statement('ALTER TABLE exam_results ADD CONSTRAINT ck_exam_results_score CHECK (score >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_results');
    }
};