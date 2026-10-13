<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Announcements (report 51): scheduled publishing and read receipts.
 *
 * - `announcements.scheduled_for` and the `scheduled` state: a draft can be
 *   scheduled; `announcements:publish-scheduled` publishes it when due. A
 *   scheduled row always has its time (CHECK).
 * - `announcement_reads`: one row per user who marked a published
 *   announcement read; removed with the announcement or the user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->timestampTz('scheduled_for')->nullable()->after('published_at');
            $table->index(['publish_state', 'scheduled_for'], 'idx_announcements_scheduled');
        });

        DB::statement('ALTER TABLE announcements DROP CONSTRAINT ck_announcements_publish');
        DB::statement("ALTER TABLE announcements ADD CONSTRAINT ck_announcements_publish CHECK (publish_state IN ('draft', 'scheduled', 'published', 'archived'))");
        DB::statement("ALTER TABLE announcements ADD CONSTRAINT ck_announcements_scheduled CHECK (publish_state <> 'scheduled' OR scheduled_for IS NOT NULL)");

        Schema::create('announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id');
            $table->foreignId('user_id');
            $table->timestampTz('read_at')->useCurrent();

            $table->unique(['announcement_id', 'user_id'], 'uq_announcement_reads');
            $table->index('user_id', 'idx_announcement_reads_user');

            $table->foreign('announcement_id', 'fk_announcement_reads_announcement')
                ->references('id')->on('announcements')->cascadeOnDelete();
            $table->foreign('user_id', 'fk_announcement_reads_user')
                ->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_reads');

        DB::table('announcements')->where('publish_state', 'scheduled')->update(['publish_state' => 'draft']);
        DB::statement('ALTER TABLE announcements DROP CONSTRAINT ck_announcements_scheduled');
        DB::statement('ALTER TABLE announcements DROP CONSTRAINT ck_announcements_publish');
        DB::statement("ALTER TABLE announcements ADD CONSTRAINT ck_announcements_publish CHECK (publish_state IN ('draft', 'published', 'archived'))");

        Schema::table('announcements', function (Blueprint $table) {
            $table->dropIndex('idx_announcements_scheduled');
            $table->dropColumn('scheduled_for');
        });
    }
};
