<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internship_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id');
            $table->string('evaluator_type', 30);
            $table->string('evaluator_name', 150)->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->string('rating', 20)->nullable();
            $table->text('comments')->nullable();
            $table->timestampTz('evaluated_at')->nullable();
            $table->foreignId('submitted_by')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('internship_id', 'idx_internship_evaluations_internship');

            $table->foreign('internship_id', 'fk_internship_evaluations_internship')
                ->references('id')->on('internships')->restrictOnDelete();
            $table->foreign('submitted_by', 'fk_internship_evaluations_submitter')
                ->references('id')->on('users')->nullOnDelete();
        });

        DB::statement('ALTER TABLE internship_evaluations ADD CONSTRAINT ck_internship_evaluations_type CHECK (evaluator_type IN (\'supervisor\', \'faculty\'))');
        DB::statement('ALTER TABLE internship_evaluations ADD CONSTRAINT ck_internship_evaluations_score CHECK (score BETWEEN 0 AND 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('internship_evaluations');
    }
};