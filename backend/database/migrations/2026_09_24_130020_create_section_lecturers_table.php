<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_lecturers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id');
            $table->foreignId('lecturer_id');
            $table->string('role', 20)->default('primary');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['section_id', 'lecturer_id'], 'uq_section_lecturers');
            $table->index('lecturer_id', 'idx_section_lecturers_lecturer');

            $table->foreign('section_id', 'fk_section_lecturers_section')
                ->references('id')->on('sections')->cascadeOnDelete();
            $table->foreign('lecturer_id', 'fk_section_lecturers_lecturer')
                ->references('id')->on('lecturers')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE section_lecturers ADD CONSTRAINT ck_section_lecturers_role CHECK (role IN (\'primary\', \'assistant\', \'tutor\'))');
        DB::statement('CREATE UNIQUE INDEX uq_section_lecturers_primary ON section_lecturers (section_id) WHERE role = \'primary\'');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS uq_section_lecturers_primary');
        Schema::dropIfExists('section_lecturers');
    }
};