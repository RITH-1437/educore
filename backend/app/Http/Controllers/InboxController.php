<?php

namespace App\Http\Controllers;

use App\Http\Resources\InboxNotificationResource;
use App\Services\InboxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The in-app inbox page (`docs/42_In-App-Notification-Inbox-Report.md`) —
 * every signed-in user, own notifications only. The unread count shown on the
 * top-bar bell is the shared `auth.unread_notifications` prop.
 */
class InboxController extends Controller
{
    public function __construct(private readonly InboxService $inbox) {}

    public function index(Request $request): Response
    {
        $status = $request->validate(['filters.status' => ['nullable', Rule::in(InboxService::FILTERS)]])['filters']['status'] ?? null;

        return Inertia::render('Notifications/Inbox', [
            'notifications' => InboxNotificationResource::collection($this->inbox->list($request->user(), $status, 20)->withQueryString()),
            'filters' => ['status' => $status],
        ]);
    }

    /** Opening a message marks it read and follows its in-app link. */
    public function open(Request $request, string $notification): RedirectResponse
    {
        return redirect($this->inbox->destination($this->inbox->markRead($request->user(), $notification)));
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $this->inbox->markRead($request->user(), $notification);

        return back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $marked = $this->inbox->markAllRead($request->user());

        return back()->with('success', $marked === 1 ? '1 notification marked as read.' : "{$marked} notifications marked as read.");
    }
}
