<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;

/**
 * The signed-in user's in-app notification inbox
 * (`docs/42_In-App-Notification-Inbox-Report.md`). Rows are written by the
 * `database` channel of every EduCore notification; this service only reads
 * them, marks them read and prunes old ones. Every lookup goes through the
 * user's own relation, so another user's notification is simply not found.
 */
class InboxService
{
    public const FILTERS = ['unread', 'read'];

    /** Stored notifications older than this are deleted daily (`notifications:prune`). */
    public const RETENTION_DAYS = 180;

    public function list(User $user, ?string $filter = null, int $perPage = 15): LengthAwarePaginator
    {
        return $user->notifications()
            ->when($filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($filter === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->paginate(min(max($perPage, 1), 100));
    }

    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function find(User $user, string $id): DatabaseNotification
    {
        return $user->notifications()->whereKey($id)->firstOrFail();
    }

    public function markRead(User $user, string $id): DatabaseNotification
    {
        $notification = $this->find($user, $id);
        $notification->markAsRead();

        return $notification;
    }

    /** @return int the number of notifications marked read */
    public function markAllRead(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }

    /**
     * Where opening a notification leads: its stored in-app path, or the inbox
     * when it has none. Only same-site paths are followed (never `//host` or a
     * full URL), so a stored value can never redirect off EduCore.
     */
    public function destination(DatabaseNotification $notification): string
    {
        $url = $notification->data['url'] ?? null;

        return is_string($url) && preg_match('#^/(?![/\\\\])#', $url) ? $url : '/inbox';
    }

    /** @return int the number of notifications deleted */
    public function prune(int $days = self::RETENTION_DAYS): int
    {
        return DatabaseNotification::query()->where('created_at', '<', now()->subDays($days))->delete();
    }
}
