<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Str;

/** A published announcement for a member of its audience (optional channels). */
class AnnouncementPublished extends EduCoreNotification
{
    public function __construct(public Announcement $announcement)
    {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Announcement: '.$this->announcement->title)
            ->greeting($this->announcement->title)
            ->line(Str::limit($this->announcement->body, 2000))
            ->action('Open announcements', $this->link('/announcements'));
    }

    public function toTelegram(object $notifiable): string
    {
        return "Announcement: {$this->announcement->title}\n\n".Str::limit($this->announcement->body, 3000)."\n\n".$this->link('/announcements');
    }

    /** @return array{kind: string, title: string, body: string, url: string} */
    public function toInbox(object $notifiable): array
    {
        return ['kind' => 'announcement', 'title' => $this->announcement->title, 'body' => Str::limit($this->announcement->body, 280), 'url' => '/announcements'];
    }
}
