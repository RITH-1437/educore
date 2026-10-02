<?php

namespace App\Notifications;

use App\Models\Enrollment;
use Illuminate\Notifications\Messages\MailMessage;

/** Course registration confirmed (optional channels). */
class EnrollmentConfirmed extends EduCoreNotification
{
    public function __construct(public Enrollment $enrollment)
    {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Registration confirmed: {$this->course()}")
            ->line("You are registered in {$this->course()}, section {$this->enrollment->section->code}.")
            ->action('Open my timetable', $this->link('/timetable'));
    }

    public function toTelegram(object $notifiable): string
    {
        return "Registration confirmed: {$this->course()}, section {$this->enrollment->section->code}.\n\n".$this->link('/timetable');
    }

    private function course(): string
    {
        $course = $this->enrollment->loadMissing('section.offering.course')->section->offering->course;

        return "{$course->code} {$course->name}";
    }
}
