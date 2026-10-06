<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TelegramLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Telegram Bot API webhook (report 48). Public, but every call must carry the
 * secret Telegram echoes from `setWebhook` (`TELEGRAM_WEBHOOK_SECRET`) in the
 * `X-Telegram-Bot-Api-Secret-Token` header; without a configured secret the
 * endpoint does not exist (404). Register it with `php artisan telegram:webhook`.
 */
class TelegramWebhookController extends Controller
{
    #[OA\Post(
        path: '/telegram/webhook',
        summary: 'Telegram bot updates (called by Telegram)',
        description: 'Handles `/start <token>` (links the private chat to the account that created the token), `/stop` (unlinks it) and replies with instructions otherwise. The reply is returned as a Bot API method in the response body. Requires the `X-Telegram-Bot-Api-Secret-Token` header; 404 when no webhook secret is configured. Rate limited.',
        operationId: 'telegramWebhook',
        tags: ['Notifications'],
        parameters: [new OA\HeaderParameter(name: 'X-Telegram-Bot-Api-Secret-Token', required: true, schema: new OA\Schema(type: 'string'))],
        requestBody: new OA\RequestBody(required: true, description: 'A Telegram Update object.', content: new OA\JsonContent(type: 'object')),
        responses: [
            new OA\Response(response: 200, description: 'Handled; either `{"ok": true}` or a `sendMessage` reply.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'method', type: 'string', example: 'sendMessage'),
                new OA\Property(property: 'chat_id', type: 'string'),
                new OA\Property(property: 'text', type: 'string'),
            ])),
            new OA\Response(response: 403, description: 'Wrong or missing secret.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'No webhook secret configured.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function __invoke(Request $request, TelegramLinkService $telegram): JsonResponse
    {
        $secret = (string) config('services.telegram.webhook_secret');
        abort_if($secret === '', 404);
        abort_unless(hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token')), 403);

        return response()->json($telegram->handle($request->all()) ?? ['ok' => true]);
    }
}
