<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Course materials (`docs/44_Course-Materials-Report.md`): handouts, slides and
 * links a section's lecturers share with its students. A `file` material keeps
 * its object in the `files` table (fileable = CourseMaterial, private MinIO);
 * a `link` material stores its URL here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('kind', 10);
            $table->string('url', 2048)->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index(['section_id', 'created_at'], 'idx_course_materials_section');

            $table->foreign('section_id', 'fk_course_materials_section')
                ->references('id')->on('sections')->restrictOnDelete();
            $table->foreign('created_by', 'fk_course_materials_creator')
                ->references('id')->on('users')->nullOnDelete();
        });

        DB::statement("ALTER TABLE course_materials ADD CONSTRAINT ck_course_materials_kind CHECK (kind IN ('file', 'link'))");
        DB::statement("ALTER TABLE course_materials ADD CONSTRAINT ck_course_materials_url CHECK (kind <> 'link' OR url IS NOT NULL)");
    }

    public function down(): void
    {
        Schema::dropIfExists('course_materials');
    }
};
