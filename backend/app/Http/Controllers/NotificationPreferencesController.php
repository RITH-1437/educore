<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\NotificationPreferenceController as Api;
use App\Http\Requests\NotificationPreferenceRequest;
use App\Notifications\TestNotification;
use App\Services\TelegramLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Notification settings page (modules 9.20 / 9.21) — every signed-in user
 * manages only their own channels.
 */
class NotificationPreferencesController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Notifications/Preferences', ['preferences' => Api::payload($request->user())]);
    }

    public function update(NotificationPreferenceRequest $request): RedirectResponse
    {
        Api::save($request->user(), $request->validated());

        return back()->with('success', 'Notification settings saved.');
    }

    public function test(Request $request): RedirectResponse
    {
        $request->user()->notify(new TestNotification);

        return back()->with('success', 'Test notification queued on your enabled channels.');
    }

    /** Off to Telegram with a one-time link; pressing Start there links the chat (report 48). */
    public function telegramLink(Request $request, TelegramLinkService $telegram): HttpResponse
    {
        return Inertia::location($telegram->linkFor($request->user())['url']);
    }

    public function unlinkTelegram(Request $request, TelegramLinkService $telegram): RedirectResponse
    {
        $telegram->unlink($request->user());

        return back()->with('success', 'Telegram disconnected.');
    }
}
