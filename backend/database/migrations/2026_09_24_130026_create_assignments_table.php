<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->decimal('max_score', 6, 2)->default(100);
            $table->timestampTz('due_at');
            $table->string('assignment_type', 20)->default('homework');
            $table->decimal('weight_override', 5, 2)->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index(['section_id', 'due_at'], 'idx_assignments_section_due');

            $table->foreign('section_id', 'fk_assignments_section')
                ->references('id')->on('sections')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE assignments ADD CONSTRAINT ck_assignments_max_score CHECK (max_score > 0)');
        DB::statement('ALTER TABLE assignments ADD CONSTRAINT ck_assignments_type CHECK (assignment_type IN (\'homework\', \'quiz\', \'project\', \'presentation\', \'other\'))');
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};