<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id');
            $table->string('letter_grade', 5)->nullable();
            $table->decimal('grade_point', 3, 2)->nullable();
            $table->decimal('total_score', 6, 2)->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('graded_by')->nullable();
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->softDeletesTz();

            $table->unique('enrollment_id', 'uq_grades_enrollment');
            $table->index(['status', 'approved_at'], 'idx_grades_status_approved');

            $table->foreign('enrollment_id', 'fk_grades_enrollment')
                ->references('id')->on('enrollments')->restrictOnDelete();
            $table->foreign('graded_by', 'fk_grades_graded_by')
                ->references('id')->on('users')->nullOnDelete();
        });

        DB::statement('ALTER TABLE grades ADD CONSTRAINT ck_grades_status CHECK (status IN (\'draft\', \'submitted\', \'approved\', \'finalized\'))');
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};