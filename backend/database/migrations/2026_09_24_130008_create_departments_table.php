<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id');
            $table->string('code', 50);
            $table->string('name', 255);
            $table->string('head_name', 255)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique('code', 'uq_departments_code');
            $table->index('faculty_id', 'idx_departments_faculty');

            $table->foreign('faculty_id', 'fk_departments_faculty')
                ->references('id')->on('faculties')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};