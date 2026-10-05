<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Notifications\Messages\MailMessage;

/** A payment or a reversal was recorded on the student's invoice (critical email). */
class PaymentRecorded extends EduCoreNotification
{
    protected bool $critical = true;

    public function __construct(public Payment $payment)
    {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(($this->payment->is_reversal ? 'Payment reversed' : 'Payment received').' — '.$this->invoice()->invoice_number)
            ->line($this->sentence())
            ->line('Remaining balance: '.number_format($this->invoice()->balanceCents() / 100, 2).' '.$this->invoice()->currency.'.')
            ->action('View invoice', $this->link("/invoices/{$this->invoice()->id}"));
    }

    public function toTelegram(object $notifiable): string
    {
        return $this->sentence()."\n\n".$this->link("/invoices/{$this->invoice()->id}");
    }

    /** @return array{kind: string, title: string, body: string, url: string} */
    public function toInbox(object $notifiable): array
    {
        return [
            'kind' => 'finance',
            'title' => ($this->payment->is_reversal ? 'Payment reversed' : 'Payment received').' — '.$this->invoice()->invoice_number,
            'body' => $this->sentence().' Remaining balance: '.number_format($this->invoice()->balanceCents() / 100, 2).' '.$this->invoice()->currency.'.',
            'url' => "/invoices/{$this->invoice()->id}",
        ];
    }

    private function sentence(): string
    {
        $amount = number_format((float) $this->payment->amount, 2).' '.$this->invoice()->currency;

        return $this->payment->is_reversal
            ? "A payment of {$amount} on invoice {$this->invoice()->invoice_number} was reversed".($this->payment->notes ? " ({$this->payment->notes})" : '').'.'
            : "We recorded your payment of {$amount} on invoice {$this->invoice()->invoice_number} ({$this->payment->paid_on->toDateString()}).";
    }

    private function invoice(): Invoice
    {
        return $this->payment->loadMissing('invoice')->invoice;
    }
}
