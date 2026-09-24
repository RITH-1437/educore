<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id');
            $table->foreignId('document_type_id');
            $table->foreignId('academic_year_id')->nullable();
            $table->foreignId('semester_id')->nullable();
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestampTz('submitted_at')->useCurrent();
            $table->foreignId('processed_by')->nullable();
            $table->timestampTz('processed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index(['status', 'submitted_at'], 'idx_document_requests_status');
            $table->index('student_id', 'idx_document_requests_student');

            $table->foreign('student_id', 'fk_document_requests_student')
                ->references('id')->on('students')->restrictOnDelete();
            $table->foreign('document_type_id', 'fk_document_requests_type')
                ->references('id')->on('document_types')->restrictOnDelete();
            $table->foreign('academic_year_id', 'fk_document_requests_year')
                ->references('id')->on('academic_years')->restrictOnDelete();
            $table->foreign('semester_id', 'fk_document_requests_semester')
                ->references('id')->on('semesters')->restrictOnDelete();
            $table->foreign('processed_by', 'fk_document_requests_processor')
                ->references('id')->on('users')->nullOnDelete();
        });

        DB::statement('ALTER TABLE document_requests ADD CONSTRAINT ck_document_requests_status CHECK (status IN (\'pending\', \'approved\', \'rejected\', \'generated\'))');
    }

    public function down(): void
    {
        Schema::dropIfExists('document_requests');
    }
};