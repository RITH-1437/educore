<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScheduleEntryRequest;
use App\Http\Resources\ScheduleEntryResource;
use App\Models\Lecturer;
use App\Models\ScheduleEntry;
use App\Models\Section;
use App\Models\Student;
use App\Services\TimetableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Section schedules and personal timetables (module 9.10).
 */
class ScheduleController extends Controller
{
    public function __construct(
        private readonly TimetableService $timetable,
    ) {}

    #[OA\Get(
        path: '/sections/{section}/schedule',
        summary: "A section's weekly meetings",
        operationId: 'getSectionSchedule',
        tags: ['Timetable'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'section', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Schedule entries.', content: new OA\JsonContent(ref: '#/components/schemas/ScheduleEntryCollection')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Role may not view sections.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Section $section): AnonymousResourceCollection
    {
        $this->authorize('view', $section->offering);

        return ScheduleEntryResource::collection($section->scheduleEntries()->with('room')->get());
    }

    #[OA\Post(
        path: '/sections/{section}/schedule',
        summary: 'Add a weekly meeting',
        description: 'Rejects (422) end ≤ start, times outside 06:00–22:00, inactive rooms, rooms smaller than current enrollment, and any overlap (incl. partial) with the same section, the same room, a lecturer of the section, or enrolled students — within the semester. 409 in a completed semester.',
        operationId: 'addScheduleEntry',
        tags: ['Timetable'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'section', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ScheduleEntryRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Entry created.', content: new OA\JsonContent(ref: '#/components/schemas/ScheduleEntryResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Semester completed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid slot or conflict.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function store(ScheduleEntryRequest $request, Section $section): JsonResponse
    {
        $this->authorize('update', $section->offering);

        $entry = $this->timetable->addEntry($section, $request->validated());

        return (new ScheduleEntryResource($entry->load('room')))->response()->setStatusCode(201);
    }

    #[OA\Put(
        path: '/schedule-entries/{entry}',
        summary: 'Move a weekly meeting',
        description: 'Same checks as creating one (the entry itself is ignored when looking for overlaps).',
        operationId: 'updateScheduleEntry',
        tags: ['Timetable'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'entry', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ScheduleEntryRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Entry updated.', content: new OA\JsonContent(ref: '#/components/schemas/ScheduleEntryResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid slot or conflict.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function update(ScheduleEntryRequest $request, ScheduleEntry $entry): ScheduleEntryResource
    {
        $this->authorize('update', $entry->section->offering);

        return new ScheduleEntryResource($this->timetable->updateEntry($entry, $request->validated())->load('room'));
    }

    #[OA\Delete(
        path: '/schedule-entries/{entry}',
        summary: 'Remove a weekly meeting',
        operationId: 'deleteScheduleEntry',
        tags: ['Timetable'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'entry', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 204, description: 'Removed.'),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(ScheduleEntry $entry): JsonResponse
    {
        $this->authorize('update', $entry->section->offering);

        $this->timetable->removeEntry($entry);

        return response()->json(null, 204);
    }

    #[OA\Get(
        path: '/timetable/student/{student}',
        summary: "A student's weekly timetable",
        description: 'Staff may read any student; a student only their own.',
        operationId: 'getStudentTimetable',
        tags: ['Timetable'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'student', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Meetings ordered by day and time.', content: new OA\JsonContent(ref: '#/components/schemas/TimetableResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not allowed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function student(Student $student): JsonResponse
    {
        $this->authorize('view', $student);

        return response()->json(['data' => $this->timetable->forStudent($student)]);
    }

    #[OA\Get(
        path: '/timetable/lecturer/{lecturer}',
        summary: "A lecturer's weekly timetable",
        description: 'Staff may read any lecturer; a lecturer only their own.',
        operationId: 'getLecturerTimetable',
        tags: ['Timetable'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'lecturer', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Meetings ordered by day and time.', content: new OA\JsonContent(ref: '#/components/schemas/TimetableResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not allowed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function lecturer(Lecturer $lecturer): JsonResponse
    {
        $this->authorize('view', $lecturer);

        return response()->json(['data' => $this->timetable->forLecturer($lecturer)]);
    }
}
