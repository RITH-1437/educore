<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id');
            $table->string('exam_type', 20);
            $table->string('title', 255);
            $table->decimal('weight', 5, 2)->default(0);
            $table->decimal('max_score', 6, 2)->default(100);
            $table->date('scheduled_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location', 255)->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index(['section_id', 'exam_type'], 'idx_exams_section_type');

            $table->foreign('section_id', 'fk_exams_section')
                ->references('id')->on('sections')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE exams ADD CONSTRAINT ck_exams_type CHECK (exam_type IN (\'midterm\', \'final\', \'quiz\', \'practical\', \'other\'))');
        DB::statement('ALTER TABLE exams ADD CONSTRAINT ck_exams_weight CHECK (weight >= 0)');
        DB::statement('ALTER TABLE exams ADD CONSTRAINT ck_exams_max_score CHECK (max_score > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};