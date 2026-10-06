<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a user keep Telegram on but turn off the class-start reminders
 * (`docs/48_Class-Reminders-and-Telegram-Linking-Report.md`). On by default:
 * reminders only ever go to a linked Telegram chat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->boolean('class_reminders')->default(true)->after('telegram_chat_id');
        });
    }

    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->dropColumn('class_reminders');
        });
    }
};
