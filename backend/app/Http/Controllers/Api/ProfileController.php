<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $profileService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->profileService->build($request->user()),
        ]);
    }

    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        $user = $this->profileService->update(
            $request->user(),
            $request->validated(),
            $request->file('avatar')
        );

        return response()->json([
            'message' => 'Profile updated successfully.',
            'data' => $this->profileService->build($user),
        ]);
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
