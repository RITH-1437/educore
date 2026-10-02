<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\NotificationPreferenceController as Api;
use App\Http\Requests\NotificationPreferenceRequest;
use App\Notifications\TestNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
}
