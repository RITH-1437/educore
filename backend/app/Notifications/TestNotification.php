<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/** Sent from the preferences page so a user can check their channels. */
class TestNotification extends EduCoreNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('EduCore test notification')
            ->line('Your email notifications are working.')
            ->action('Notification settings', $this->link('/notifications'));
    }

    public function toTelegram(object $notifiable): string
    {
        return 'EduCore: your Telegram notifications are working.';
    }
}
