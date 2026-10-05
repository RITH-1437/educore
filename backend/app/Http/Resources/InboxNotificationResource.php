<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

/**
 * One in-app inbox message (`docs/42_In-App-Notification-Inbox-Report.md`).
 * The text was rendered when the notification was sent; the notification
 * class name is not exposed.
 *
 * @mixin DatabaseNotification
 */
class InboxNotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->data['kind'] ?? 'general',
            'title' => $this->data['title'] ?? '',
            'body' => $this->data['body'] ?? '',
            'url' => $this->data['url'] ?? null,
            'read_at' => $this->read_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
