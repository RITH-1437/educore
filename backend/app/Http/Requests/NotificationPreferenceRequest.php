<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The signed-in user's own channels. A Telegram chat id is the numeric id the
 * bot reports for the user's chat (negative for groups).
 */
class NotificationPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notify_by_email' => ['required', 'boolean'],
            'notify_by_telegram' => ['required', 'boolean'],
            'telegram_chat_id' => ['nullable', 'string', 'regex:/^-?\d{4,20}$/', 'required_if_accepted:notify_by_telegram'],
            'class_reminders' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'telegram_chat_id.regex' => 'Enter the numeric chat id the EduCore bot gave you.',
            'telegram_chat_id.required_if_accepted' => 'Link a Telegram chat id to turn on Telegram notifications.',
        ];
    }
}
