<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id');
            $table->foreignId('company_id');
            $table->string('position_title', 255);
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('supervisor_name', 150)->nullable();
            $table->string('supervisor_email', 150)->nullable();
            $table->string('supervisor_phone', 50)->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestampTz('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index(['student_id', 'status'], 'idx_internships_student_status');
            $table->index('company_id', 'idx_internships_company');

            $table->foreign('student_id', 'fk_internships_student')
                ->references('id')->on('students')->restrictOnDelete();
            $table->foreign('company_id', 'fk_internships_company')
                ->references('id')->on('internship_companies')->restrictOnDelete();
            $table->foreign('reviewed_by', 'fk_internships_reviewer')
                ->references('id')->on('users')->nullOnDelete();
        });

        DB::statement('ALTER TABLE internships ADD CONSTRAINT ck_internships_status CHECK (status IN (\'draft\', \'submitted\', \'under_review\', \'approved\', \'rejected\', \'in_progress\', \'completed\', \'cancelled\'))');
        DB::statement('ALTER TABLE internships ADD CONSTRAINT ck_internships_dates CHECK (end_date IS NULL OR start_date IS NULL OR end_date >= start_date)');
        DB::statement('CREATE UNIQUE INDEX uq_internships_active ON internships (student_id) WHERE status IN (\'submitted\', \'under_review\', \'approved\', \'in_progress\')');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS uq_internships_active');
        Schema::dropIfExists('internships');
    }
};