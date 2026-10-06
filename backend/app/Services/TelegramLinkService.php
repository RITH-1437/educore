<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * One-tap Telegram linking (module 9.21, report 48). Instead of looking up a
 * numeric chat id, a user opens a t.me deep link carrying a one-time token and
 * presses Start; Telegram calls the webhook with `/start <token>` from that
 * chat, and the chat is linked to the account that asked for the link.
 *
 * - Tokens: 40 random characters, single use, 15 minutes, kept in the cache
 *   under their SHA-256 (the raw token is never stored).
 * - Only private chats are linked; `/stop` from a linked chat unlinks it.
 * - Links and unlinks are audited (`notifications.telegram_linked` / `_unlinked`).
 * - Replies go back in the webhook response (Bot API "method" reply), so the
 *   webhook makes no outgoing call.
 */
class TelegramLinkService
{
    public const TOKEN_MINUTES = 15;

    public function __construct(private readonly AuditLogger $audit) {}

    /** Linking needs the bot token (to send) and its username (for t.me links). */
    public function available(): bool
    {
        return filled(config('services.telegram.bot_token')) && filled(config('services.telegram.bot_username'));
    }

    /**
     * A one-time deep link that connects the chat in which the user presses Start.
     *
     * @return array{url: string, expires_at: string}
     */
    public function linkFor(User $user): array
    {
        if (! $this->available()) {
            throw new BusinessRuleException('Telegram linking is not set up on this server yet. Enter your chat id instead.');
        }

        $token = Str::random(40);
        $expires = now()->addMinutes(self::TOKEN_MINUTES);
        Cache::put($this->key($token), $user->getKey(), $expires);

        return [
            'url' => 'https://t.me/'.ltrim((string) config('services.telegram.bot_username'), '@').'?start='.$token,
            'expires_at' => $expires->toIso8601String(),
        ];
    }

    /**
     * Handle one webhook update; returns the Bot API reply, or null to stay silent.
     *
     * @param  array<string, mixed>  $update
     * @return array{method: string, chat_id: string, text: string}|null
     */
    public function handle(array $update): ?array
    {
        $message = is_array($update['message'] ?? null) ? $update['message'] : null;
        $chat = is_array($message['chat'] ?? null) ? $message['chat'] : null;
        $text = trim((string) ($message['text'] ?? ''));

        if ($chat === null || ! isset($chat['id']) || ! str_starts_with($text, '/')) {
            return null;
        }

        $chatId = (string) $chat['id'];
        if (($chat['type'] ?? null) !== 'private') {
            return $this->reply($chatId, 'EduCore links only private chats. Open a private chat with this bot and try again.');
        }

        if (preg_match('/^\/start(?:@\w+)?\s+([A-Za-z0-9]{20,64})$/', $text, $match) === 1) {
            $userId = Cache::pull($this->key($match[1]));
            $user = $userId ? User::query()->where('is_active', true)->find($userId) : null;

            if ($user === null) {
                return $this->reply($chatId, 'This link has expired or was already used. In EduCore, open Notification settings and choose Connect Telegram for a new one.');
            }

            $this->link($user, $chatId);

            return $this->reply($chatId, "Connected. EduCore will send {$user->name}'s notifications to this chat. Send /stop to disconnect.");
        }

        if (preg_match('/^\/stop(?:@\w+)?$/', $text) === 1) {
            return $this->reply($chatId, $this->unlinkChat($chatId) > 0
                ? 'Disconnected. EduCore will no longer message this chat.'
                : 'This chat is not linked to an EduCore account.');
        }

        return $this->reply($chatId, 'To receive EduCore notifications here, open Notification settings in EduCore and choose Connect Telegram.');
    }

    /** Link a chat to the user and turn Telegram delivery on. */
    public function link(User $user, string $chatId): void
    {
        DB::transaction(function () use ($user, $chatId) {
            NotificationPreference::query()->updateOrCreate(['user_id' => $user->getKey()], ['telegram_chat_id' => $chatId, 'notify_by_telegram' => true]);
            $this->audit->record('notifications.telegram_linked', $user, after: ['telegram_chat' => $this->mask($chatId)], description: 'Telegram chat linked to the account.', actor: $user);
        });
    }

    /** Forget the user's chat (from the settings page); read fresh, not from a cached relation. */
    public function unlink(User $user): void
    {
        $this->forget(NotificationPreference::query()->where('user_id', $user->getKey())->first(), $user);
    }

    /** `/stop` from Telegram: unlink every account using this chat. */
    private function unlinkChat(string $chatId): int
    {
        $preferences = NotificationPreference::query()->with('user')->where('telegram_chat_id', $chatId)->get();

        foreach ($preferences as $preference) {
            if ($preference->user !== null) {
                $this->forget($preference, $preference->user);
            }
        }

        return $preferences->count();
    }

    private function forget(?NotificationPreference $preference, User $user): void
    {
        if ($preference === null || blank($preference->telegram_chat_id)) {
            return;
        }

        DB::transaction(function () use ($user, $preference) {
            $chat = $this->mask((string) $preference->telegram_chat_id);
            $preference->update(['telegram_chat_id' => null]);
            $user->unsetRelation('notificationPreference');
            $this->audit->record('notifications.telegram_unlinked', $user, before: ['telegram_chat' => $chat], description: 'Telegram chat unlinked from the account.', actor: $user);
        });
    }

    /**
     * @return array{method: string, chat_id: string, text: string}
     */
    private function reply(string $chatId, string $text): array
    {
        return ['method' => 'sendMessage', 'chat_id' => $chatId, 'text' => $text];
    }

    private function key(string $token): string
    {
        return 'telegram-link:'.hash('sha256', $token);
    }

    /** Audit entries identify the chat without storing its full id. */
    private function mask(string $chatId): string
    {
        return '…'.substr($chatId, -4);
    }
}
