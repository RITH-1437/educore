<?php

namespace App\Notifications;

use App\Models\DocumentRequest;
use Illuminate\Notifications\Messages\MailMessage;

/** A document request was approved, rejected or its PDF is ready (critical email). */
class DocumentRequestUpdated extends EduCoreNotification
{
    protected bool $critical = true;

    public function __construct(public DocumentRequest $request)
    {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject("Document request {$this->status()}: {$this->name()}")->line($this->sentence());

        if ($this->request->status === DocumentRequest::STATUS_REJECTED && $this->request->rejection_reason) {
            $mail->line('Reason: '.$this->request->rejection_reason);
        }

        return $mail->action('Open my documents', $this->link('/my-documents'));
    }

    public function toTelegram(object $notifiable): string
    {
        return $this->sentence()
            .($this->request->status === DocumentRequest::STATUS_REJECTED && $this->request->rejection_reason ? "\nReason: {$this->request->rejection_reason}" : '')
            ."\n\n".$this->link('/my-documents');
    }

    /** @return array{kind: string, title: string, body: string, url: string} */
    public function toInbox(object $notifiable): array
    {
        $reason = $this->request->status === DocumentRequest::STATUS_REJECTED && $this->request->rejection_reason ? ' Reason: '.$this->request->rejection_reason : '';

        return ['kind' => 'document', 'title' => 'Document request '.$this->status().': '.$this->name(), 'body' => $this->sentence().$reason, 'url' => '/my-documents'];
    }

    private function sentence(): string
    {
        return match ($this->request->status) {
            DocumentRequest::STATUS_APPROVED => "Your request for a {$this->name()} was approved. You will be notified when the PDF is ready.",
            DocumentRequest::STATUS_REJECTED => "Your request for a {$this->name()} was rejected.",
            DocumentRequest::STATUS_GENERATED => "Your {$this->name()} is ready to download.",
            default => "Your request for a {$this->name()} is {$this->request->status}.",
        };
    }

    private function status(): string
    {
        return $this->request->status === DocumentRequest::STATUS_GENERATED ? 'ready' : $this->request->status;
    }

    private function name(): string
    {
        return strtolower($this->request->loadMissing('type')->type->name);
    }
}
