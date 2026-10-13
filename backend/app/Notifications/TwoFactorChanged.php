<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * Security notice when two-factor sign-in changes on an account (report 52):
 * turned on or off, reset by a Super Admin, or new recovery codes. Critical:
 * sent by email even when email notices are off; Telegram if linked.
 */
class TwoFactorChanged extends EduCoreNotification
{
    public const ENABLED = 'enabled';

    public const DISABLED = 'disabled';

    public const RESET = 'reset';

    public const NEW_RECOVERY_CODES = 'recovery_codes';

    protected bool $critical = true;

    public function __construct(public string $change)
    {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('EduCore security: '.$this->title())
            ->line($this->sentence())
            ->line('If this was not you, change your password and contact the university office immediately.')
            ->action('Two-factor sign-in', $this->link('/account/two-factor'));
    }

    public function toTelegram(object $notifiable): string
    {
        return $this->sentence().' If this was not you, change your password and contact the university office immediately.';
    }

    /** @return array{kind: string, title: string, body: string, url: string} */
    public function toInbox(object $notifiable): array
    {
        return ['kind' => 'security', 'title' => $this->title(), 'body' => $this->sentence().' If this was not you, contact the university office immediately.', 'url' => '/account/two-factor'];
    }

    private function title(): string
    {
        return match ($this->change) {
            self::ENABLED => 'Two-factor sign-in turned on',
            self::DISABLED => 'Two-factor sign-in turned off',
            self::RESET => 'Two-factor sign-in reset by an administrator',
            default => 'New two-factor recovery codes',
        };
    }

    private function sentence(): string
    {
        $when = now()->timezone(config('app.timezone'))->format('j M Y, H:i');

        return match ($this->change) {
            self::ENABLED => "Two-factor sign-in was turned on for your EduCore account on {$when}. Signing in now also needs a code from your authenticator app.",
            self::DISABLED => "Two-factor sign-in was turned off for your EduCore account on {$when}.",
            self::RESET => "An administrator turned off two-factor sign-in for your EduCore account on {$when}. You can set it up again from your account.",
            default => "New two-factor recovery codes were created for your EduCore account on {$when}; the previous codes no longer work.",
        };
    }
}
