<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Notification channels of the signed-in user (modules 9.20 / 9.21). */
#[OA\Schema(
    schema: 'NotificationPreferences',
    type: 'object',
    properties: [
        new OA\Property(property: 'notify_by_email', type: 'boolean', description: 'Optional emails (announcements, grades, registration, reminders). Critical ones are always sent.'),
        new OA\Property(property: 'notify_by_telegram', type: 'boolean'),
        new OA\Property(property: 'telegram_chat_id', type: 'string', nullable: true),
        new OA\Property(property: 'telegram_enabled', type: 'boolean', description: 'Whether the server has a Telegram bot configured.'),
        new OA\Property(property: 'telegram_linkable', type: 'boolean', description: 'Whether one-tap linking (POST /notification-preferences/telegram-link) is available.'),
        new OA\Property(property: 'class_reminders', type: 'boolean', description: 'Telegram reminders before each class (report 48); need a linked chat.'),
        new OA\Property(property: 'class_reminder_minutes', type: 'integer', description: 'How many minutes before a class the reminder is sent.'),
        new OA\Property(property: 'email', type: 'string', format: 'email'),
    ]
)]
#[OA\Schema(
    schema: 'NotificationPreferencesRequest',
    type: 'object',
    required: ['notify_by_email', 'notify_by_telegram'],
    properties: [
        new OA\Property(property: 'notify_by_email', type: 'boolean'),
        new OA\Property(property: 'notify_by_telegram', type: 'boolean'),
        new OA\Property(property: 'telegram_chat_id', type: 'string', nullable: true, pattern: '^-?\d{4,20}$', description: 'Required when notify_by_telegram is true.'),
        new OA\Property(property: 'class_reminders', type: 'boolean', description: 'Optional; omitted keeps the saved value.'),
    ]
)]
class NotificationPreferences {}
