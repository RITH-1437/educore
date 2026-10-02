<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Notifications\AnnouncementPublished;
use App\Services\AnnouncementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

/**
 * Fans a published announcement out to its audience (module 9.20 / 9.21):
 * resolves recipients in the queue, not in the request, and hands them to
 * `AnnouncementPublished` in chunks (each notification is queued again per
 * user, so one failing mailbox never blocks the rest).
 */
class SendAnnouncementNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Announcement $announcement)
    {
        $this->onQueue('notifications');
        $this->afterCommit();
    }

    public function handle(AnnouncementService $announcements): void
    {
        if ($this->announcement->publish_state !== Announcement::STATE_PUBLISHED) {
            return;
        }

        $announcements->recipients($this->announcement)
            ->with('notificationPreference')
            ->chunkById(500, fn ($users) => Notification::send($users, new AnnouncementPublished($this->announcement)));
    }
}
