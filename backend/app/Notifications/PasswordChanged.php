<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/** Security notice after a password change or reset (critical email; Telegram if linked). */
class PasswordChanged extends EduCoreNotification
{
    protected bool $critical = true;

    public function __construct(public string $how = 'changed')
    {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your EduCore password was '.$this->how)
            ->line($this->sentence())
            ->line('Other signed-in sessions have been signed out.')
            ->line('If this was not you, contact the university office immediately.');
    }

    public function toTelegram(object $notifiable): string
    {
        return $this->sentence().' If this was not you, contact the university office immediately.';
    }

    /** @return array{kind: string, title: string, body: string, url: null} */
    public function toInbox(object $notifiable): array
    {
        return ['kind' => 'security', 'title' => 'Your password was '.$this->how, 'body' => $this->sentence().' Other signed-in sessions have been signed out. If this was not you, contact the university office immediately.', 'url' => null];
    }

    private function sentence(): string
    {
        return 'The password of your EduCore account was '.$this->how.' on '.now()->timezone(config('app.timezone'))->format('j M Y, H:i').'.';
    }
}
