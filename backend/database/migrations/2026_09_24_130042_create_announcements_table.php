<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id');
            $table->string('title', 255);
            $table->text('body');
            $table->string('announcement_type', 30)->nullable();
            $table->string('audience_type', 30)->default('all');
            $table->bigInteger('audience_id')->nullable();
            $table->string('publish_state', 20)->default('draft');
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index(['audience_type', 'audience_id'], 'idx_announcements_audience');
            $table->index(['publish_state', 'published_at'], 'idx_announcements_published');

            $table->foreign('author_id', 'fk_announcements_author')
                ->references('id')->on('users')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE announcements ADD CONSTRAINT ck_announcements_type CHECK (announcement_type IS NULL OR announcement_type IN (\'general\', \'academic\', \'administrative\', \'event\'))');
        DB::statement('ALTER TABLE announcements ADD CONSTRAINT ck_announcements_audience CHECK (audience_type IN (\'all\', \'students\', \'lecturers\', \'staff\', \'faculty\', \'department\', \'program\', \'section\', \'course\'))');
        DB::statement('ALTER TABLE announcements ADD CONSTRAINT ck_announcements_publish CHECK (publish_state IN (\'draft\', \'published\', \'archived\'))');
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};