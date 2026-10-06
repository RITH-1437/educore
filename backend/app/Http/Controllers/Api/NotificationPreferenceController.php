<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationPreferenceRequest;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\TestNotification;
use App\Services\TelegramLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * The signed-in user's notification channels (modules 9.20 / 9.21). Every
 * call acts on the caller only — there is no user id in the path.
 */
class NotificationPreferenceController extends Controller
{
    #[OA\Get(
        path: '/notification-preferences',
        summary: 'My notification preferences',
        description: 'Defaults (email on, Telegram on but inactive without a chat id) when none are saved. `telegram_enabled` says whether the server has a bot configured. Critical messages (document status, invoices and payments) are always emailed.',
        operationId: 'getNotificationPreferences',
        tags: ['Notifications'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Preferences.', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/NotificationPreferences')])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => self::payload($request->user())]);
    }

    #[OA\Put(
        path: '/notification-preferences',
        summary: 'Update my notification preferences',
        operationId: 'updateNotificationPreferences',
        tags: ['Notifications'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/NotificationPreferencesRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Saved.', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/NotificationPreferences')])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function update(NotificationPreferenceRequest $request): JsonResponse
    {
        self::save($request->user(), $request->validated());

        return response()->json(['data' => self::payload($request->user()->refresh())]);
    }

    #[OA\Post(
        path: '/notification-preferences/test',
        summary: 'Send me a test notification',
        description: 'Queued on every channel the caller has enabled. Rate limited (3 per minute).',
        operationId: 'testNotification',
        tags: ['Notifications'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 202, description: 'Queued.', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 429, description: 'Too many test messages.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function test(Request $request): JsonResponse
    {
        $request->user()->notify(new TestNotification);

        return response()->json(['message' => 'Test notification queued.'], 202);
    }

    #[OA\Post(
        path: '/notification-preferences/telegram-link',
        summary: 'Get a one-time link that connects my Telegram',
        description: 'Opening the t.me link and pressing Start in the bot links that private chat to the caller and turns Telegram on (report 48). Single use, valid for 15 minutes. 409 when the server has no bot username / token. Rate limited (5 per minute).',
        operationId: 'createTelegramLink',
        tags: ['Notifications'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Link created.', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', properties: [
                new OA\Property(property: 'url', type: 'string', format: 'uri', example: 'https://t.me/EduCoreBot?start=…'),
                new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
            ], type: 'object')])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Telegram linking is not set up on this server.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 429, description: 'Too many links requested.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function telegramLink(Request $request, TelegramLinkService $telegram): JsonResponse
    {
        return response()->json(['data' => $telegram->linkFor($request->user())], 201);
    }

    #[OA\Delete(
        path: '/notification-preferences/telegram',
        summary: 'Disconnect my Telegram chat',
        description: 'Forgets the linked chat id (audited). Sending /stop to the bot does the same.',
        operationId: 'unlinkTelegram',
        tags: ['Notifications'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Disconnected.', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/NotificationPreferences')])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function unlinkTelegram(Request $request, TelegramLinkService $telegram): JsonResponse
    {
        $telegram->unlink($request->user());

        return response()->json(['data' => self::payload($request->user()->refresh())]);
    }

    /**
     * @param  array{notify_by_email: bool, notify_by_telegram: bool, telegram_chat_id?: string|null, class_reminders?: bool}  $data
     */
    public static function save(User $user, array $data): NotificationPreference
    {
        return NotificationPreference::query()->updateOrCreate(['user_id' => $user->getKey()], [
            'notify_by_email' => (bool) $data['notify_by_email'],
            'notify_by_telegram' => (bool) $data['notify_by_telegram'],
            'telegram_chat_id' => $data['telegram_chat_id'] ?? null,
            // Optional for older clients: an omitted toggle keeps its saved value.
            'class_reminders' => (bool) ($data['class_reminders'] ?? $user->preferences()->class_reminders),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function payload(User $user): array
    {
        $preferences = $user->preferences();

        return [
            'notify_by_email' => $preferences->notify_by_email,
            'notify_by_telegram' => $preferences->notify_by_telegram,
            'telegram_chat_id' => $preferences->telegram_chat_id,
            'telegram_enabled' => filled(config('services.telegram.bot_token')),
            'telegram_linkable' => app(TelegramLinkService::class)->available(),
            'class_reminders' => $preferences->class_reminders,
            'class_reminder_minutes' => (int) config('academics.class_reminder_minutes', 30),
            'email' => $user->email,
        ];
    }
}
