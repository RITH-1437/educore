<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The signed-in user's own profile (`docs/45_Profile-Portal-Report.md`):
 * no user id in the read / update paths, so nobody edits another account here.
 */
class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $profileService,
    ) {}

    #[OA\Get(
        path: '/profile',
        summary: 'My profile',
        description: 'Account, role and department, avatar, and — for a linked student or lecturer — the academic or teaching summary.',
        operationId: 'showProfile',
        tags: ['Profile'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'The profile.', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Profile')])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->profileService->build($request->user()),
        ]);
    }

    #[OA\Put(
        path: '/profile',
        summary: 'Update my profile (JSON)',
        description: 'Name and phone for everyone; address and emergency contact for students; specialization for lecturers (other roles\' extra fields are ignored). `avatar_url` sets an external http(s) image; `remove_avatar` clears it. To upload an image file use POST with multipart.',
        operationId: 'updateProfile',
        tags: ['Profile'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['name'], properties: [
            new OA\Property(property: 'name', type: 'string', maxLength: 255),
            new OA\Property(property: 'phone', type: 'string', nullable: true, maxLength: 30),
            new OA\Property(property: 'address', type: 'string', nullable: true, maxLength: 255, description: 'Students only.'),
            new OA\Property(property: 'emergency_contact_name', type: 'string', nullable: true, maxLength: 255, description: 'Students only.'),
            new OA\Property(property: 'emergency_contact_phone', type: 'string', nullable: true, maxLength: 30, description: 'Students only.'),
            new OA\Property(property: 'specialization', type: 'string', nullable: true, maxLength: 255, description: 'Lecturers only.'),
            new OA\Property(property: 'avatar_url', type: 'string', format: 'uri', nullable: true, maxLength: 2048),
            new OA\Property(property: 'remove_avatar', type: 'boolean'),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Updated.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Profile'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    #[OA\Post(
        path: '/profile',
        summary: 'Update my profile with an avatar upload (multipart)',
        description: 'Same fields as PUT, plus `avatar`: a jpg, png or webp image up to 2 MB. Precedence: `remove_avatar`, then `avatar`, then `avatar_url`. The replaced uploaded image is deleted.',
        operationId: 'updateProfileMultipart',
        tags: ['Profile'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(required: ['name'], properties: [
            new OA\Property(property: 'name', type: 'string', maxLength: 255),
            new OA\Property(property: 'phone', type: 'string', nullable: true, maxLength: 30),
            new OA\Property(property: 'address', type: 'string', nullable: true, maxLength: 255, description: 'Students only.'),
            new OA\Property(property: 'emergency_contact_name', type: 'string', nullable: true, maxLength: 255, description: 'Students only.'),
            new OA\Property(property: 'emergency_contact_phone', type: 'string', nullable: true, maxLength: 30, description: 'Students only.'),
            new OA\Property(property: 'specialization', type: 'string', nullable: true, maxLength: 255, description: 'Lecturers only.'),
            new OA\Property(property: 'avatar', type: 'string', format: 'binary', description: 'jpg, png or webp; max 2 MB.'),
            new OA\Property(property: 'avatar_url', type: 'string', format: 'uri', nullable: true, maxLength: 2048),
            new OA\Property(property: 'remove_avatar', type: 'boolean'),
        ]))),
        responses: [
            new OA\Response(response: 200, description: 'Updated.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Profile'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
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

    #[OA\Get(
        path: '/users/{user}/avatar',
        summary: 'A user\'s uploaded avatar image',
        description: 'Public, like a profile photo. Streams an uploaded avatar inline. An external (URL) avatar is not served or redirected here — read `avatar_url` from the profile instead.',
        operationId: 'showUserAvatar',
        tags: ['Profile'],
        parameters: [new OA\PathParameter(name: 'user', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'The image (jpg, png or webp).'),
            new OA\Response(response: 404, description: 'No uploaded avatar.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function avatar(User $user): StreamedResponse
    {
        return $this->profileService->avatarResponse($user);
    }
}
