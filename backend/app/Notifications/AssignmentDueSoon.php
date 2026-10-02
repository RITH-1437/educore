<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Notifications\Messages\MailMessage;

/** Reminder for a published, unsubmitted assignment due within a day (optional channels). */
class AssignmentDueSoon extends EduCoreNotification
{
    public function __construct(public Assignment $assignment)
    {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Due soon: {$this->assignment->title}")
            ->line("“{$this->assignment->title}” ({$this->course()}) is due {$this->due()} and you have not submitted it yet.")
            ->action('Open my assignments', $this->link('/my-assignments'));
    }

    public function toTelegram(object $notifiable): string
    {
        return "Reminder: “{$this->assignment->title}” ({$this->course()}) is due {$this->due()}.\n\n".$this->link('/my-assignments');
    }

    private function course(): string
    {
        return $this->assignment->loadMissing('section.offering.course')->section->offering->course->code;
    }

    private function due(): string
    {
        return $this->assignment->due_at->timezone(config('app.timezone'))->format('D j M, H:i');
    }
}
