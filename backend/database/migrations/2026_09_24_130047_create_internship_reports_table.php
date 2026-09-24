<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internship_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id');
            $table->string('report_type', 30)->default('progress');
            $table->string('title', 255);
            $table->text('summary')->nullable();
            $table->timestampTz('submitted_at')->useCurrent();
            $table->string('status', 20)->default('submitted');
            $table->text('reviewer_comment')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('internship_id', 'idx_internship_reports_internship');

            $table->foreign('internship_id', 'fk_internship_reports_internship')
                ->references('id')->on('internships')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE internship_reports ADD CONSTRAINT ck_internship_reports_type CHECK (report_type IN (\'initial\', \'progress\', \'final\'))');
        DB::statement('ALTER TABLE internship_reports ADD CONSTRAINT ck_internship_reports_status CHECK (status IN (\'draft\', \'submitted\', \'reviewed\'))');
    }

    public function down(): void
    {
        Schema::dropIfExists('internship_reports');
    }
};