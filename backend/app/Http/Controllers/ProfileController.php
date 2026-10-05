<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $profileService,
    ) {}

    public function show(Request $request): Response
    {
        return Inertia::render('Account/Profile', [
            'profile' => $this->profileService->build($request->user()),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $this->profileService->update(
            $request->user(),
            $request->validated(),
            $request->file('avatar')
        );

        return back()->with('success', 'Profile updated successfully.');
    }

    public function avatar(User $user): mixed
    {
        if (empty($user->avatar_key)) {
            abort(404, 'No avatar set.');
        }

        if (str_starts_with($user->avatar_key, 'http://') || str_starts_with($user->avatar_key, 'https://')) {
            return redirect()->away($user->avatar_key);
        }

        $disk = $this->profileService->disk();
        if (! Storage::disk($disk)->exists($user->avatar_key)) {
            abort(404, 'Avatar file not found.');
        }

        return Storage::disk($disk)->response($user->avatar_key, headers: [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
