<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecordAttendanceRequest;
use App\Models\AttendanceSession;
use App\Models\Section;
use App\Models\Student;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Attendance endpoints (module 9.11). The section's lecturers record; managers
 * may correct; Faculty Admin reads; a student reads only their own summary.
 */
class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendance,
    ) {}

    #[OA\Post(
        path: '/sections/{section}/attendance',
        summary: 'Record attendance for a date (bulk)',
        description: 'Creates or updates the session for `session_date` and upserts one record per listed enrollment; unlisted students are untouched. Date must not be in the future, must be inside the semester and — when the section has a schedule — on a scheduled weekday. Only enrollments of this section (pending/confirmed/completed) are accepted.',
        operationId: 'recordAttendance',
        tags: ['Attendance'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'section', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/RecordAttendanceRequest')),
        responses: [
            new OA\Response(response: 200, description: 'The session with its roster.', content: new OA\JsonContent(ref: '#/components/schemas/AttendanceRosterResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a lecturer of this section or a manager.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Semester completed or session cancelled.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid date, status or enrollment.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function record(RecordAttendanceRequest $request, Section $section): JsonResponse
    {
        $this->authorize('record', [AttendanceSession::class, $section]);

        $data = $request->validated();
        $this->attendance->record($section, $data['session_date'], $data['records'], $request->user(), $data['topic'] ?? null);

        return $this->rosterResponse($section, $data['session_date']);
    }

    #[OA\Get(
        path: '/sections/{section}/attendance',
        summary: 'Roster and statuses for one date',
        operationId: 'getAttendanceRoster',
        tags: ['Attendance'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'section', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
            new OA\QueryParameter(name: 'date', required: true, description: 'YYYY-MM-DD', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Session (null when not recorded) and roster.', content: new OA\JsonContent(ref: '#/components/schemas/AttendanceRosterResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not allowed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Missing or invalid date.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function show(Request $request, Section $section): JsonResponse
    {
        $this->authorize('viewSection', [AttendanceSession::class, $section]);

        $date = $request->validate(['date' => ['required', 'date_format:Y-m-d']])['date'];

        return $this->rosterResponse($section, $date);
    }

    #[OA\Get(
        path: '/sections/{section}/attendance/summary',
        summary: 'Per-student counts and attendance rate',
        description: 'rate = (present + late) / (present + late + absent) over held sessions; excused, unmarked and cancelled are excluded; null when nothing counts yet.',
        operationId: 'getAttendanceSummary',
        tags: ['Attendance'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'section', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Summary rows.', content: new OA\JsonContent(ref: '#/components/schemas/AttendanceSummaryResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not allowed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function summary(Section $section): JsonResponse
    {
        $this->authorize('viewSection', [AttendanceSession::class, $section]);

        return response()->json(['data' => $this->attendance->sectionSummary($section)]);
    }

    #[OA\Get(
        path: '/students/{student}/attendance',
        summary: "A student's attendance per course",
        description: 'Staff may read any student; a student only their own.',
        operationId: 'getStudentAttendance',
        tags: ['Attendance'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'student', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Counts and rate per enrollment.', content: new OA\JsonContent(ref: '#/components/schemas/AttendanceSummaryResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not allowed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function student(Student $student): JsonResponse
    {
        $this->authorize('viewStudent', [AttendanceSession::class, $student]);

        return response()->json(['data' => $this->attendance->studentSummary($student)]);
    }

    #[OA\Post(
        path: '/attendance-sessions/{session}/cancel',
        summary: 'Cancel or restore a session',
        description: 'A cancelled session keeps its records but is excluded from rates. Send `cancelled: false` to restore it.',
        operationId: 'cancelAttendanceSession',
        tags: ['Attendance'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'session', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [new OA\Property(property: 'cancelled', type: 'boolean', default: true)])),
        responses: [
            new OA\Response(response: 200, description: 'Session updated.'),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not allowed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Semester completed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function cancel(Request $request, AttendanceSession $session): JsonResponse
    {
        $this->authorize('record', [AttendanceSession::class, $session->section]);

        $session = $this->attendance->cancel($session, $request->boolean('cancelled', true));

        return response()->json(['data' => ['id' => $session->id, 'status' => $session->status, 'session_date' => $session->session_date->toDateString()]]);
    }

    private function rosterResponse(Section $section, string $date): JsonResponse
    {
        ['session' => $session, 'roster' => $roster] = $this->attendance->roster($section, $date);

        return response()->json(['data' => [
            'section_id' => $section->id,
            'date' => $date,
            'session' => $session ? ['id' => $session->id, 'status' => $session->status, 'topic' => $session->topic, 'start_time' => $session->start_time ? substr($session->start_time, 0, 5) : null, 'end_time' => $session->end_time ? substr($session->end_time, 0, 5) : null] : null,
            'roster' => $roster,
        ]]);
    }
}
