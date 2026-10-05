<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InboxNotificationResource;
use App\Services\InboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * The signed-in user's in-app inbox (`docs/42_In-App-Notification-Inbox-Report.md`).
 * Always the caller's own notifications: there is no user id in any path, and
 * another user's notification id answers 404.
 */
class NotificationController extends Controller
{
    public function __construct(private readonly InboxService $inbox) {}

    #[OA\Get(
        path: '/notifications',
        summary: 'My in-app notifications',
        description: 'Newest first. `filters[status]` narrows to `unread` or `read`; `meta.unread_count` is the caller\'s unread total whatever the filter.',
        operationId: 'listNotifications',
        tags: ['Notifications'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'filters[status]', required: false, schema: new OA\Schema(type: 'string', enum: InboxService::FILTERS)),
            new OA\QueryParameter(name: 'per_page', required: false, schema: new OA\Schema(type: 'integer', maximum: 100)),
            new OA\QueryParameter(name: 'page', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Notifications.', content: new OA\JsonContent(ref: '#/components/schemas/InboxNotificationCollection')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid filter.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'filters.status' => ['nullable', Rule::in(InboxService::FILTERS)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $notifications = $this->inbox->list($request->user(), $validated['filters']['status'] ?? null, (int) ($validated['per_page'] ?? 15))->withQueryString();

        return InboxNotificationResource::collection($notifications)
            ->additional(['meta' => ['unread_count' => $this->inbox->unreadCount($request->user())]]);
    }

    #[OA\Post(
        path: '/notifications/{notification}/read',
        summary: 'Mark one of my notifications read',
        operationId: 'markNotificationRead',
        tags: ['Notifications'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'notification', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        responses: [
            new OA\Response(response: 200, description: 'The notification, now read.', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/InboxNotification')])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not one of the caller\'s notifications.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function read(Request $request, string $notification): InboxNotificationResource
    {
        return new InboxNotificationResource($this->inbox->markRead($request->user(), $notification));
    }

    #[OA\Post(
        path: '/notifications/read-all',
        summary: 'Mark all my notifications read',
        operationId: 'markAllNotificationsRead',
        tags: ['Notifications'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'How many were marked.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'marked', type: 'integer'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function readAll(Request $request): JsonResponse
    {
        $marked = $this->inbox->markAllRead($request->user());

        return response()->json(['message' => 'All notifications marked as read.', 'marked' => $marked]);
    }
}
