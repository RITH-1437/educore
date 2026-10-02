<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * The emailed password-reset link (critical, email only — a reset link never
 * goes to Telegram). Replaces Laravel's default so it is queued with the
 * other notifications and carries EduCore wording.
 */
class ResetPasswordLink extends EduCoreNotification
{
    protected bool $critical = true;

    public function __construct(public string $token)
    {
        parent::__construct();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return array_values(array_intersect(parent::via($notifiable), ['mail']));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutes = (int) config('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject('Reset your EduCore password')
            ->line('We received a request to reset the password of your EduCore account.')
            ->action('Choose a new password', $this->link('/reset-password/'.$this->token.'?email='.urlencode($notifiable->getEmailForPasswordReset())))
            ->line("The link works once and expires in {$minutes} minutes.")
            ->line('If you did not ask for this, you can ignore this email — your password stays the same.');
    }
}
