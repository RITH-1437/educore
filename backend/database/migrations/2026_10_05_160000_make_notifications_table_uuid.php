<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * In-app notification inbox (`docs/42_In-App-Notification-Inbox-Report.md`).
 *
 * Laravel's database channel stores a UUID primary key, while the original
 * `notifications` table had a bigint identity and was never written (report
 * 25). The table is recreated with a UUID key; every other column, type and
 * index keeps the documented definition (`docs/database/schema-tables.sql`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('notifications');

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->morphs('notifiable', 'idx_notifications_notifiable');
            $table->string('type', 255);
            $table->jsonb('data');
            $table->timestampTz('read_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('read_at', 'idx_notifications_read');
        });
    }

    /** Restores the original bigint table (stored notifications are dropped). */
    public function down(): void
    {
        Schema::dropIfExists('notifications');

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->morphs('notifiable', 'idx_notifications_notifiable');
            $table->string('type', 255);
            $table->jsonb('data')->nullable();
            $table->timestampTz('read_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('read_at', 'idx_notifications_read');
        });
    }
};
