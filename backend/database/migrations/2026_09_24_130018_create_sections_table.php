<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_offering_id');
            $table->string('code', 20);
            $table->string('name', 100)->nullable();
            $table->smallInteger('capacity')->default(30);
            $table->string('status', 20)->default('draft');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['course_offering_id', 'code'], 'uq_sections_offering_code');
            $table->index('course_offering_id', 'idx_sections_offering');

            $table->foreign('course_offering_id', 'fk_sections_offering')
                ->references('id')->on('course_offerings')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE sections ADD CONSTRAINT ck_sections_capacity CHECK (capacity >= 0)');
        DB::statement('ALTER TABLE sections ADD CONSTRAINT ck_sections_status CHECK (status IN (\'draft\', \'open\', \'active\', \'closed\', \'archived\'))');
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};