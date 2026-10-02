<?php

namespace App\Notifications;

use App\Models\Grade;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * A course grade was approved (optional channels). The message names the
 * course only; the grade itself is shown after sign-in.
 */
class GradePublished extends EduCoreNotification
{
    public function __construct(public Grade $grade)
    {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Grade published: {$this->course()}")
            ->line("Your final grade for {$this->course()} has been approved.")
            ->action('Open Grades & GPA', $this->link('/my-grades'));
    }

    public function toTelegram(object $notifiable): string
    {
        return "Your final grade for {$this->course()} has been approved.\n\n".$this->link('/my-grades');
    }

    private function course(): string
    {
        $course = $this->grade->loadMissing('enrollment.section.offering.course')->enrollment->section->offering->course;

        return "{$course->code} {$course->name}";
    }
}
