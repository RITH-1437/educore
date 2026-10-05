<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'is_active' => $user->is_active,
                    'role' => $user->role ? [
                        'id' => $user->role->id,
                        'name' => $user->role->name,
                        'slug' => $user->role->slug,
                    ] : null,
                ] : null,
                // Top-bar bell badge (in-app inbox, report 42).
                'unread_notifications' => $user ? $user->unreadNotifications()->count() : 0,
            ],
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
            'frontend' => [
                'url' => config('frontend.url'),
                'api_url' => config('frontend.api_url'),
                'api_prefix' => config('frontend.api_prefix'),
                'dev_server_url' => config('frontend.dev_server_url'),
            ],
        ]);
    }
}
