<?php

namespace App\Notifications;

use App\Models\Internship;
use Illuminate\Notifications\Messages\MailMessage;

/** An internship application was decided or the internship ended (critical email). */
class InternshipStatusChanged extends EduCoreNotification
{
    protected bool $critical = true;

    public function __construct(public Internship $internship)
    {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Internship '.str_replace('_', ' ', $this->internship->status).': '.$this->internship->position_title)
            ->line($this->sentence())
            ->action('Open my internship', $this->link('/my-internships'));
    }

    public function toTelegram(object $notifiable): string
    {
        return $this->sentence()."\n\n".$this->link('/my-internships');
    }

    /** @return array{kind: string, title: string, body: string, url: string} */
    public function toInbox(object $notifiable): array
    {
        return ['kind' => 'internship', 'title' => 'Internship '.str_replace('_', ' ', $this->internship->status).': '.$this->internship->position_title, 'body' => $this->sentence(), 'url' => '/my-internships'];
    }

    private function sentence(): string
    {
        $what = "your internship “{$this->internship->position_title}” at {$this->internship->loadMissing('company')->company->name}";

        return match ($this->internship->status) {
            Internship::STATUS_APPROVED => 'Good news: '.$what.' was approved.',
            Internship::STATUS_REJECTED => ucfirst($what).' was not approved. See the review notes for the reason; you may submit a new application.',
            Internship::STATUS_IN_PROGRESS => ucfirst($what).' has started. Remember to submit your progress and final reports.',
            Internship::STATUS_COMPLETED => ucfirst($what).' is marked completed.',
            Internship::STATUS_CANCELLED => ucfirst($what).' was cancelled.',
            default => ucfirst($what).' is now '.str_replace('_', ' ', $this->internship->status).'.',
        };
    }
}
