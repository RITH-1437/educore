<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnnouncementRequest;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Models\StoredFile;
use App\Services\AnnouncementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        description: 'Published announcements whose audience includes the caller (everyone, their role group, department, program, sections or courses), newest first. Each item has `is_read`; `meta.unread` counts the unread ones.',
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

        return AnnouncementResource::collection($page)->additional(['meta' => ['unread' => $this->announcements->unreadCount($request->user())]]);
    }

    #[OA\Post(
        path: '/announcements/read-all',
        summary: 'Mark every announcement in the feed read',
        operationId: 'readAllAnnouncements',
        tags: ['Announcements'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'How many were marked.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'object', properties: [new OA\Property(property: 'marked', type: 'integer')]),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function readAll(Request $request): JsonResponse
    {
        return response()->json(['data' => ['marked' => $this->announcements->markAllRead($request->user())]]);
    }

    #[OA\Post(
        path: '/announcements/{announcement}/read',
        summary: 'Mark one announcement read (read receipt)',
        description: 'Idempotent. Only a published announcement in the caller\'s feed.',
        operationId: 'readAnnouncement',
        tags: ['Announcements'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'announcement', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 204, description: 'Marked read.'),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not published, or not in the caller\'s feed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function read(Request $request, Announcement $announcement): JsonResponse
    {
        $this->authorize('read', $announcement);
        $this->announcements->markRead($announcement, $request->user());

        return response()->json(null, 204);
    }

    #[OA\Get(
        path: '/announcements',
        summary: 'Announcements the caller manages',
        description: 'Managers see all; a Department Admin or lecturer their own. Drafts first, then scheduled. Each item has `audience_size` (active users it reaches now) and `read_count` (how many of them marked it read).',
        operationId: 'listAnnouncements',
        tags: ['Announcements'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'filters[publish_state]', required: false, schema: new OA\Schema(type: 'string', enum: ['draft', 'scheduled', 'published', 'archived'])),
            new OA\QueryParameter(name: 'page', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Announcements.', content: new OA\JsonContent(ref: '#/components/schemas/AnnouncementCollection')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a manager, a Department Admin with a department, or an active lecturer.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid filter.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('manageAny', Announcement::class);

        $page = $this->announcements->managedBy($request->user(), self::stateFilter($request))->paginate(15)->withQueryString();
        $this->announcements->preloadTargets($page->getCollection());
        $this->announcements->attachReach($page->getCollection());

        return AnnouncementResource::collection($page);
    }

    #[OA\Post(
        path: '/announcements',
        summary: 'Create an announcement',
        description: 'Saved as a draft unless `publish` is true, or scheduled when `scheduled_for` is given. Department Admins may target only their department and its programs, courses and sections, lecturers only sections / courses they teach (422).',
        operationId: 'createAnnouncement',
        tags: ['Announcements'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AnnouncementRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Created.', content: new OA\JsonContent(ref: '#/components/schemas/AnnouncementResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a manager, a Department Admin with a department, or an active lecturer.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed, audience not allowed, or schedule time invalid.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function store(AnnouncementRequest $request): JsonResponse
    {
        $this->authorize('create', Announcement::class);

        $publish = $request->boolean('publish');
        $announcement = $this->announcements->create($request->user(), $request->validated(), false);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $this->announcements->attachFile($announcement, $file, $request->user());
            }
        }

        if ($publish) {
            $announcement = $this->announcements->publish($announcement, $request->user());
        } elseif ($request->filled('scheduled_for')) {
            $announcement = $this->announcements->schedule($announcement, $request->user(), (string) $request->validated('scheduled_for'));
        }

        return (new AnnouncementResource($announcement->load(['author:id,name', 'attachments'])))->response()->setStatusCode(201);
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

        return new AnnouncementResource($announcement->load(['author:id,name', 'attachments']));
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

        $announcement = $this->announcements->update($announcement, $request->user(), $request->validated());

        if ($request->has('remove_attachment_ids')) {
            foreach ((array) $request->input('remove_attachment_ids') as $id) {
                $attachment = $announcement->attachments()->find($id);
                if ($attachment) {
                    $this->announcements->deleteAttachment($announcement, $attachment);
                }
            }
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $this->announcements->attachFile($announcement, $file, $request->user());
            }
        }

        return new AnnouncementResource($announcement->load(['author:id,name', 'attachments']));
    }

    #[OA\Post(
        path: '/announcements/{announcement}/publish',
        summary: 'Publish a draft or a scheduled announcement now',
        operationId: 'publishAnnouncement',
        tags: ['Announcements'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'announcement', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Published.', content: new OA\JsonContent(ref: '#/components/schemas/AnnouncementResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not the author or a manager.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Not a draft or scheduled.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Audience no longer allowed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function publish(Request $request, Announcement $announcement): AnnouncementResource
    {
        $this->authorize('update', $announcement);

        return new AnnouncementResource($this->announcements->publish($announcement, $request->user())->load(['author:id,name', 'attachments']));
    }

    #[OA\Post(
        path: '/announcements/{announcement}/schedule',
        summary: 'Schedule a draft for later publication',
        description: '`announcements:publish-scheduled` publishes it, as its author, once due; if the author can no longer reach the audience then, it returns to draft and the author is notified.',
        operationId: 'scheduleAnnouncement',
        tags: ['Announcements'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'announcement', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['scheduled_for'], properties: [
            new OA\Property(property: 'scheduled_for', type: 'string', format: 'date-time', description: 'Future, within a year; without an offset read in the university timezone.'),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Scheduled.', content: new OA\JsonContent(ref: '#/components/schemas/AnnouncementResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not the author or a manager.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Not a draft.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Time missing, in the past or over a year away; audience not allowed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function schedule(Request $request, Announcement $announcement): AnnouncementResource
    {
        $this->authorize('update', $announcement);
        $at = $request->validate(['scheduled_for' => ['required', 'date']])['scheduled_for'];

        return new AnnouncementResource($this->announcements->schedule($announcement, $request->user(), (string) $at)->load(['author:id,name', 'attachments']));
    }

    #[OA\Post(
        path: '/announcements/{announcement}/unschedule',
        summary: 'Take a scheduled announcement back to draft',
        operationId: 'unscheduleAnnouncement',
        tags: ['Announcements'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'announcement', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Back to draft.', content: new OA\JsonContent(ref: '#/components/schemas/AnnouncementResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not the author or a manager.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Not scheduled.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function unschedule(Announcement $announcement): AnnouncementResource
    {
        $this->authorize('update', $announcement);

        return new AnnouncementResource($this->announcements->unschedule($announcement)->load(['author:id,name', 'attachments']));
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

        return new AnnouncementResource($this->announcements->archive($announcement)->load(['author:id,name', 'attachments']));
    }

    #[OA\Get(
        path: '/announcements/{announcement}/attachments/{file}/download',
        summary: 'Download an announcement attachment',
        description: 'Available to any user within the audience of the announcement, its author, or managers.',
        operationId: 'downloadAnnouncementAttachment',
        tags: ['Announcements'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'announcement', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
            new OA\PathParameter(name: 'file', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'File stream.', content: new OA\MediaType(mediaType: 'application/octet-stream')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not allowed to view announcement.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Attachment not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function downloadAttachment(Announcement $announcement, StoredFile $file): StreamedResponse
    {
        $this->authorize('view', $announcement);

        return $this->announcements->downloadAttachment($announcement, $file);
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
