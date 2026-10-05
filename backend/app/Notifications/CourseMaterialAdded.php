<?php

namespace App\Notifications;

use App\Models\CourseMaterial;
use Illuminate\Notifications\Messages\MailMessage;

/** A lecturer shared a new material in one of the student's sections (optional channels; report 44). */
class CourseMaterialAdded extends EduCoreNotification
{
    public function __construct(public CourseMaterial $material)
    {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New material in {$this->course()}: {$this->material->title}")
            ->line("“{$this->material->title}” was shared in {$this->course()}, section {$this->material->section->code}.")
            ->action('Open course materials', $this->link('/my-materials'));
    }

    public function toTelegram(object $notifiable): string
    {
        return "New material in {$this->course()}: “{$this->material->title}”.\n\n".$this->link('/my-materials');
    }

    /** @return array{kind: string, title: string, body: string, url: string} */
    public function toInbox(object $notifiable): array
    {
        return ['kind' => 'material', 'title' => "New material: {$this->material->title}", 'body' => "Shared in {$this->course()}, section {$this->material->section->code}.", 'url' => '/my-materials'];
    }

    private function course(): string
    {
        return $this->material->loadMissing('section.offering.course')->section->offering->course->code;
    }
}
