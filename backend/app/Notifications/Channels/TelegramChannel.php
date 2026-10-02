<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends a notification's `toTelegram()` text through the Telegram Bot API
 * (module 9.21) with Laravel's HTTP client — no extra package.
 *
 * Skipped silently when the bot token is not configured or the user has no
 * linked chat (`User::routeNotificationForTelegram`). An API error throws, so
 * the queued notification is retried and finally reported by `failed()`.
 */
class TelegramChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $chatId = $notifiable->routeNotificationFor('telegram', $notification);
        $token = config('services.telegram.bot_token');

        if (blank($chatId) || ! method_exists($notification, 'toTelegram')) {
            return;
        }

        if (blank($token)) {
            Log::info('Telegram notification skipped: TELEGRAM_BOT_TOKEN is not configured.', ['notification' => $notification::class]);

            return;
        }

        Http::timeout(10)
            ->asJson()
            ->post(rtrim((string) config('services.telegram.api_url'), '/')."/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $notification->toTelegram($notifiable),
                'disable_web_page_preview' => true,
            ])
            ->throw();
    }
}
