<?php

namespace App\Notifications;

use App\Models\DocumentRequest;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * A student submitted a document request; sent to the staff who can approve
 * it (`StaffNotifier`, `docs/43_Staff-Request-Notices-Report.md`). Optional
 * email: staff may turn non-critical mail off.
 */
class DocumentRequestSubmitted extends EduCoreNotification
{
    private const QUEUE = '/documents?filters[status]=pending';

    public function __construct(public DocumentRequest $request)
    {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New document request: {$this->type()} — {$this->student()}")
            ->line("{$this->student()} requested a ".strtolower($this->type())."{$this->semester()}.")
            ->action('Open the request queue', $this->link(self::QUEUE));
    }

    public function toTelegram(object $notifiable): string
    {
        return "New document request: {$this->type()}{$this->semester()} for {$this->student()}.\n\n".$this->link(self::QUEUE);
    }

    /** @return array{kind: string, title: string, body: string, url: string} */
    public function toInbox(object $notifiable): array
    {
        return ['kind' => 'document', 'title' => "New document request: {$this->type()}", 'body' => "{$this->student()} is waiting for approval{$this->semester()}.", 'url' => self::QUEUE];
    }

    private function type(): string
    {
        return $this->request->loadMissing('type')->type->name;
    }

    private function student(): string
    {
        $student = $this->request->loadMissing('student')->student;

        return "{$student->fullName()} ({$student->student_number})";
    }

    private function semester(): string
    {
        $semester = $this->request->loadMissing('semester')->semester;

        return $semester ? " for {$semester->name}" : '';
    }
}
