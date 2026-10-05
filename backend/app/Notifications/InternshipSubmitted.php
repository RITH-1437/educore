<?php

namespace App\Notifications;

use App\Models\Internship;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * A student submitted an internship application; sent to the staff who can
 * review it (`StaffNotifier`, `docs/43_Staff-Request-Notices-Report.md`).
 * Optional email: staff may turn non-critical mail off.
 */
class InternshipSubmitted extends EduCoreNotification
{
    public function __construct(public Internship $internship)
    {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New internship application: {$this->student()}")
            ->line("{$this->student()} applied for {$this->placement()}{$this->period()}.")
            ->action('Review the application', $this->link($this->path()));
    }

    public function toTelegram(object $notifiable): string
    {
        return "New internship application from {$this->student()}: {$this->placement()}{$this->period()}.\n\n".$this->link($this->path());
    }

    /** @return array{kind: string, title: string, body: string, url: string} */
    public function toInbox(object $notifiable): array
    {
        return ['kind' => 'internship', 'title' => "New internship application: {$this->student()}", 'body' => ucfirst($this->placement())."{$this->period()} is waiting for review.", 'url' => $this->path()];
    }

    private function path(): string
    {
        return "/internships/{$this->internship->id}";
    }

    private function student(): string
    {
        $student = $this->internship->loadMissing('student')->student;

        return "{$student->fullName()} ({$student->student_number})";
    }

    private function placement(): string
    {
        return "“{$this->internship->position_title}” at {$this->internship->loadMissing('company')->company->name}";
    }

    private function period(): string
    {
        return $this->internship->start_date && $this->internship->end_date
            ? ', '.$this->internship->start_date->toDateString().' to '.$this->internship->end_date->toDateString()
            : '';
    }
}
