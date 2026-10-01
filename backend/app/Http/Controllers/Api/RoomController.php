<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use App\Services\TimetableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Room endpoints (module 9.10).
 */
class RoomController extends Controller
{
    public function __construct(
        private readonly TimetableService $timetable,
    ) {}

    #[OA\Get(
        path: '/rooms',
        summary: 'List rooms',
        operationId: 'listRooms',
        tags: ['Timetable'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Paginated rooms.', content: new OA\JsonContent(ref: '#/components/schemas/RoomCollection')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Role may not view rooms.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Room::class);

        return RoomResource::collection(Room::query()->withCount('scheduleEntries')->orderBy('code')->paginate(50));
    }

    #[OA\Post(
        path: '/rooms',
        summary: 'Create a room',
        operationId: 'createRoom',
        tags: ['Timetable'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/RoomRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Room created.', content: new OA\JsonContent(ref: '#/components/schemas/RoomResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function store(RoomRequest $request): JsonResponse
    {
        $this->authorize('create', Room::class);

        return (new RoomResource($this->timetable->createRoom($request->validated())))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/rooms/{room}',
        summary: 'Fetch a room',
        operationId: 'getRoom',
        tags: ['Timetable'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'room', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'The room.', content: new OA\JsonContent(ref: '#/components/schemas/RoomResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Role may not view rooms.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Room $room): RoomResource
    {
        $this->authorize('view', $room);

        return new RoomResource($room->loadCount('scheduleEntries'));
    }

    #[OA\Put(
        path: '/rooms/{room}',
        summary: 'Update a room',
        description: 'Set `is_active` false to retire a room; inactive rooms cannot receive new classes.',
        operationId: 'updateRoom',
        tags: ['Timetable'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'room', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/RoomRequest')),
        responses: [
            new OA\Response(response: 200, description: 'The updated room.', content: new OA\JsonContent(ref: '#/components/schemas/RoomResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function update(RoomRequest $request, Room $room): RoomResource
    {
        $this->authorize('update', $room);

        return new RoomResource($this->timetable->updateRoom($room, $request->validated()));
    }

    #[OA\Delete(
        path: '/rooms/{room}',
        summary: 'Delete a room',
        description: 'Refused with 409 while classes are scheduled in it; deactivate instead.',
        operationId: 'deleteRoom',
        tags: ['Timetable'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'room', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 204, description: 'Deleted.'),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'The room has scheduled classes.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Room $room): JsonResponse
    {
        $this->authorize('delete', $room);

        $this->timetable->deleteRoom($room);

        return response()->json(null, 204);
    }
}
