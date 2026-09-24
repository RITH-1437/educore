<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id');
            $table->string('code', 50);
            $table->string('name', 255);
            $table->string('dean_name', 255)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->softDeletesTz();

            $table->unique('code', 'uq_faculties_code');
            $table->index('university_id', 'idx_faculties_university');

            $table->foreign('university_id', 'fk_faculties_university')
                ->references('id')->on('universities')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculties');
    }
};