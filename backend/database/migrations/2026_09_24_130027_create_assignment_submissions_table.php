<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id');
            $table->foreignId('enrollment_id');
            $table->timestampTz('submitted_at')->useCurrent();
            $table->string('status', 20)->default('submitted');
            $table->decimal('score', 6, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->foreignId('graded_by')->nullable();
            $table->timestampTz('graded_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['assignment_id', 'enrollment_id'], 'uq_assignment_submissions');
            $table->index('enrollment_id', 'idx_assignment_submissions_enrollment');

            $table->foreign('assignment_id', 'fk_assignment_submissions_assignment')
                ->references('id')->on('assignments')->restrictOnDelete();
            $table->foreign('enrollment_id', 'fk_assignment_submissions_enrollment')
                ->references('id')->on('enrollments')->restrictOnDelete();
            $table->foreign('graded_by', 'fk_assignment_submissions_grader')
                ->references('id')->on('users')->nullOnDelete();
        });

        DB::statement('ALTER TABLE assignment_submissions ADD CONSTRAINT ck_assignment_submissions_status CHECK (status IN (\'submitted\', \'late\', \'graded\', \'returned\'))');
        DB::statement('ALTER TABLE assignment_submissions ADD CONSTRAINT ck_assignment_submissions_score CHECK (score >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
    }
};