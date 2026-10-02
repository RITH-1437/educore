<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnnouncementRequest;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Services\AnnouncementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Announcements (module 9.19).
 */
class AnnouncementController extends Controller
{
    public function __construct(
        private readonly AnnouncementService $announcements,
    ) {}

    #[OA\Get(
        path: '/announcements/feed',
        summary: "The signed-in user's announcement feed",
        description: 'Published announcements whose audience includes the caller (everyone, their role group, faculty, department, program, sections or courses), newest first.',
        operationId: 'announcementFeed',
        tags: ['Announcements'],
        security: [['sanctum' => []]],
        parameters: [new OA\QueryParameter(name: 'page', required: false, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Feed.', content: new OA\JsonContent(ref: '#/components/schemas/AnnouncementCollection')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function feed(Request $request): AnonymousResourceCollection
    {
        $page = $this->announcements->feedFor($request->user())->paginate(15)->withQueryString();
        $this->announcements->preloadTargets($page->getCollection());

        return AnnouncementResource::collection($page);
    }

    #[OA\Get(
        path: '/announcements',
        summary: 'Announcements the caller manages',
        description: 'Managers see all; a lecturer sees their own. Drafts first.',
        operationId: 'listAnnouncements',
        tags: ['Announcements'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'filters[publish_state]', required: false, schema: new OA\Schema(type: 'string', enum: ['draft', 'published', 'archived'])),
            new OA\QueryParameter(name: 'page', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Announcements.', content: new OA\JsonContent(ref: '#/components/schemas/AnnouncementCollection')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a manager or an active lecturer.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid filter.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('manageAny', Announcement::class);

        $page = $this->announcements->managedBy($request->user(), self::stateFilter($request))->paginate(15)->withQueryString();
        $this->announcements->preloadTargets($page->getCollection());

        return AnnouncementResource::collection($page);
    }

    #[OA\Post(
        path: '/announcements',
        summary: 'Create an announcement',
        description: 'Saved as a draft unless `publish` is true. Lecturers may target only sections / courses they teach (422).',
        operationId: 'createAnnouncement',
        tags: ['Announcements'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AnnouncementRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Created.', content: new OA\JsonContent(ref: '#/components/schemas/AnnouncementResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a manager or an active lecturer.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed or audience not allowed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function store(AnnouncementRequest $request): JsonResponse
    {
        $this->authorize('create', Announcement::class);

        $announcement = $this->announcements->create($request->user(), $request->validated(), $request->boolean('publish'));

        return (new AnnouncementResource($announcement->load('author:id,name')))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/announcements/{announcement}',
        summary: 'Fetch an announcement',
        description: 'Visible to its audience (once published), its author and managers.',
        operationId: 'getAnnouncement',
        tags: ['Announcements'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'announcement', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'The announcement.', content: new OA\JsonContent(ref: '#/components/schemas/AnnouncementResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not in the audience.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Announcement $announcement): AnnouncementResource
    {
        $this->authorize('view', $announcement);

        return new AnnouncementResource($announcement->load('author:id,name'));
    }

    #[OA\Put(
        path: '/announcements/{announcement}',
        summary: 'Edit a draft announcement',
        description: 'Published / archived announcements are never rewritten (409) — archive and publish a correction.',
        operationId: 'updateAnnouncement',
        tags: ['Announcements'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'announcement', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AnnouncementRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Updated.', content: new OA\JsonContent(ref: '#/components/schemas/AnnouncementResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not the author or a manager.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Not a draft.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    #[OA\Patch(
        path: '/announcements/{announcement}',
        summary: 'Edit a draft announcement (PATCH)',
        description: 'Same full-update validation as PUT.',
        operationId: 'patchAnnouncement',
        tags: ['Announcements'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'announcement', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AnnouncementRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Updated.', content: new OA\JsonContent(ref: '#/components/schemas/AnnouncementResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not the author or a manager.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Not a draft.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function update(AnnouncementRequest $request, Announcement $announcement): AnnouncementResource
    {
        $this->authorize('update', $announcement);

        return new AnnouncementResource($this->announcements->update($announcement, $request->user(), $request->validated())->load('author:id,name'));
    }

    #[OA\Post(
        path: '/announcements/{announcement}/publish',
        summary: 'Publish a draft',
        operationId: 'publishAnnouncement',
        tags: ['Announcements'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'announcement', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Published.', content: new OA\JsonContent(ref: '#/components/schemas/AnnouncementResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not the author or a manager.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Not a draft.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Audience no longer allowed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function publish(Request $request, Announcement $announcement): AnnouncementResource
    {
        $this->authorize('update', $announcement);

        return new AnnouncementResource($this->announcements->publish($announcement, $request->user())->load('author:id,name'));
    }

    #[OA\Post(
        path: '/announcements/{announcement}/archive',
        summary: 'Archive a published announcement',
        description: 'Removes it from feeds; the record is kept.',
        operationId: 'archiveAnnouncement',
        tags: ['Announcements'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'announcement', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Archived.', content: new OA\JsonContent(ref: '#/components/schemas/AnnouncementResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not the author or a manager.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Not published.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function archive(Announcement $announcement): AnnouncementResource
    {
        $this->authorize('update', $announcement);

        return new AnnouncementResource($this->announcements->archive($announcement)->load('author:id,name'));
    }

    #[OA\Delete(
        path: '/announcements/{announcement}',
        summary: 'Delete a draft',
        description: 'Only drafts can be deleted (409 otherwise).',
        operationId: 'deleteAnnouncement',
        tags: ['Announcements'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'announcement', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 204, description: 'Deleted.'),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not the author or a manager.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Not a draft.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Announcement $announcement): JsonResponse
    {
        $this->authorize('update', $announcement);

        $this->announcements->delete($announcement);

        return response()->json(null, 204);
    }

    public static function stateFilter(Request $request): ?string
    {
        return $request->validate(['filters.publish_state' => ['nullable', Rule::in(Announcement::STATES)]])['filters']['publish_state'] ?? null;
    }
}
