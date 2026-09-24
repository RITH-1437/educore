<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grading_scales', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('grade', 5);
            $table->decimal('min_percentage', 5, 2);
            $table->decimal('max_percentage', 5, 2);
            $table->decimal('grade_point', 3, 2);
            $table->boolean('is_pass')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['name', 'grade'], 'uq_grading_scales_name_grade');
        });

        DB::statement('ALTER TABLE grading_scales ADD CONSTRAINT ck_grading_scales_range CHECK (min_percentage <= max_percentage)');
    }

    public function down(): void
    {
        Schema::dropIfExists('grading_scales');
    }
};