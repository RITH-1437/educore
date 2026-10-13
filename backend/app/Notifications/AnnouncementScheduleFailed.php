<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * A scheduled announcement could not be published at its time and went back
 * to draft (report 51), e.g. its author no longer teaches the section. Sent to
 * the author.
 */
class AnnouncementScheduleFailed extends EduCoreNotification
{
    public function __construct(public Announcement $announcement, public string $reason)
    {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Scheduled announcement not published: '.$this->announcement->title)
            ->line("Your announcement “{$this->announcement->title}” was not published at its scheduled time and is back in your drafts.")
            ->line('Reason: '.$this->reason)
            ->action('Manage announcements', $this->link('/announcements/manage'));
    }

    public function toTelegram(object $notifiable): string
    {
        return "Scheduled announcement not published: {$this->announcement->title}\n\n{$this->reason}\nIt is back in your drafts.\n\n".$this->link('/announcements/manage');
    }

    /** @return array{kind: string, title: string, body: string, url: string} */
    public function toInbox(object $notifiable): array
    {
        return [
            'kind' => 'announcement',
            'title' => "Not published: {$this->announcement->title}",
            'body' => "Your scheduled announcement is back in your drafts. {$this->reason}",
            'url' => '/announcements/manage',
        ];
    }
}
