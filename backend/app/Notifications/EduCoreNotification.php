<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Shared channel and queue rules for every EduCore notification
 * (modules 9.20 / 9.21, `skills/notifications`):
 *
 * - Queued on `notifications` (Redis), dispatched only after the surrounding
 *   transaction commits, 3 tries with back-off, then logged by `failed()`.
 * - Email goes out when the notification is critical (document status,
 *   finance) or the user kept email on; Telegram only when the user opted in
 *   and linked a chat. Inactive accounts receive nothing.
 */
abstract class EduCoreNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    /** Critical notifications ignore the email opt-out. */
    protected bool $critical = false;

    public function __construct()
    {
        $this->onQueue('notifications');
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        if ($notifiable instanceof User && ! $notifiable->is_active) {
            return [];
        }

        $preferences = $notifiable instanceof User ? $notifiable->preferences() : null;
        $channels = [];

        if (filled($notifiable->email ?? null) && ($this->critical || ($preferences?->notify_by_email ?? true))) {
            $channels[] = 'mail';
        }

        if ($preferences?->telegramReady() && method_exists($this, 'toTelegram')) {
            $channels[] = TelegramChannel::class;
        }

        return $channels;
    }

    public function failed(Throwable $e): void
    {
        Log::warning('Notification delivery failed after retries.', ['notification' => static::class, 'error' => $e->getMessage()]);
    }

    /** Absolute link into the app for message bodies. */
    protected function link(string $path): string
    {
        return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
    }
}
