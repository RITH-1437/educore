<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id');
            $table->string('code', 50);
            $table->string('name', 255);
            $table->string('degree_level', 50);
            $table->smallInteger('duration_years')->nullable();
            $table->decimal('credits_required', 5, 1)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique('code', 'uq_programs_code');
            $table->index('department_id', 'idx_programs_department');

            $table->foreign('department_id', 'fk_programs_department')
                ->references('id')->on('departments')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};