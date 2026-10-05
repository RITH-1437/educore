<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Notifications\Messages\MailMessage;

/** A new invoice for the student (critical email). */
class InvoiceIssued extends EduCoreNotification
{
    protected bool $critical = true;

    public function __construct(public Invoice $invoice)
    {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Invoice {$this->invoice->invoice_number}: {$this->invoice->title}")
            ->line("A new invoice of {$this->amount()} has been issued to you.")
            ->line('Due date: '.$this->invoice->due_date->toDateString().'. Payments are made at the university finance office.')
            ->action('View invoice', $this->link("/invoices/{$this->invoice->id}"));
    }

    public function toTelegram(object $notifiable): string
    {
        return "New invoice {$this->invoice->invoice_number}: {$this->amount()}, due {$this->invoice->due_date->toDateString()}.\n\n".$this->link("/invoices/{$this->invoice->id}");
    }

    /** @return array{kind: string, title: string, body: string, url: string} */
    public function toInbox(object $notifiable): array
    {
        return ['kind' => 'finance', 'title' => "Invoice {$this->invoice->invoice_number}: {$this->invoice->title}", 'body' => "A new invoice of {$this->amount()} has been issued to you, due {$this->invoice->due_date->toDateString()}.", 'url' => "/invoices/{$this->invoice->id}"];
    }

    private function amount(): string
    {
        return number_format((float) $this->invoice->total, 2).' '.$this->invoice->currency;
    }
}
