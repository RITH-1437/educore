<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user's notification channels (`skills/notifications` — user preferences).
 * One row per user; a user without a row gets the defaults (email on,
 * Telegram on but inert until a chat id is linked).
 *
 * @property int $id
 * @property int $user_id
 * @property bool $notify_by_email
 * @property bool $notify_by_telegram
 * @property string|null $telegram_chat_id
 */
class NotificationPreference extends Model
{
    protected $fillable = ['user_id', 'notify_by_email', 'notify_by_telegram', 'telegram_chat_id'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['notify_by_email' => true, 'notify_by_telegram' => true];

    protected function casts(): array
    {
        return ['notify_by_email' => 'boolean', 'notify_by_telegram' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function telegramReady(): bool
    {
        return $this->notify_by_telegram && filled($this->telegram_chat_id);
    }
}
