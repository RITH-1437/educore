<?php

namespace App\Http\Controllers;

use App\Enums\SemesterStatus;
use App\Http\Requests\RecordAttendanceRequest;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Section;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Attendance screens (module 9.11): a lecturer's class list, the per-section
 * register (also reachable by staff), and a student's own attendance.
 */
class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendance,
    ) {}

    /** Lecturer: sections they teach in semesters that are not completed. */
    public function classes(Request $request): Response
    {
        $lecturer = $request->user()->lecturer;
        abort_if($lecturer === null, 403, 'No lecturer profile is linked to this account.');

        $sections = $lecturer->sections()
            ->with(['offering.course:id,code,name', 'offering.semester.academicYear:id,code'])
            ->withCount(['enrollments' => fn ($q) => $q->whereIn('status', ['pending', 'confirmed'])])
            ->get()
            ->filter(fn (Section $s) => $s->offering->semester->status !== SemesterStatus::Completed)
            ->map(fn (Section $s) => [
                'id' => $s->id,
                'code' => $s->code,
                'course' => ['code' => $s->offering->course->code, 'name' => $s->offering->course->name],
                'semester' => trim(($s->offering->semester->academicYear?->code ?? '').' '.$s->offering->semester->name),
                'students' => $s->enrollments_count,
                'role' => $s->pivot->role,
            ])
            ->values();

        return Inertia::render('Attendance/Classes', ['sections' => $sections]);
    }

    public function section(Request $request, Section $section): Response
    {
        $this->authorize('viewSection', [AttendanceSession::class, $section]);

        $section->load(['offering.course:id,code,name', 'offering.semester.academicYear:id,code']);
        $expected = $this->attendance->expectedDates($section);
        $date = $request->query('date');

        if (! is_string($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = $expected->first()['date'] ?? Carbon::today()->toDateString();
        }

        ['session' => $session, 'roster' => $roster] = $this->attendance->roster($section, $date);

        return Inertia::render('Attendance/Section', [
            'section' => [
                'id' => $section->id,
                'code' => $section->code,
                'course' => ['code' => $section->offering->course->code, 'name' => $section->offering->course->name],
                'semester' => trim(($section->offering->semester->academicYear?->code ?? '').' '.$section->offering->semester->name),
                'locked' => $section->offering->semester->status === SemesterStatus::Completed,
            ],
            'date' => $date,
            'session' => $session ? ['id' => $session->id, 'status' => $session->status, 'topic' => $session->topic] : null,
            'roster' => $roster,
            'summary' => $this->attendance->sectionSummary($section),
            'expectedDates' => $expected->take(30)->values(),
            'statuses' => AttendanceRecord::STATUSES,
            'canRecord' => $request->user()->can('record', [AttendanceSession::class, $section]),
        ]);
    }

    public function record(RecordAttendanceRequest $request, Section $section): RedirectResponse
    {
        $this->authorize('record', [AttendanceSession::class, $section]);

        $data = $request->validated();
        $this->attendance->record($section, $data['session_date'], $data['records'], $request->user(), $data['topic'] ?? null);

        return back()->with('success', 'Attendance saved.');
    }

    public function cancel(Request $request, AttendanceSession $session): RedirectResponse
    {
        $this->authorize('record', [AttendanceSession::class, $session->section]);

        $cancelled = $request->boolean('cancelled', true);
        $this->attendance->cancel($session, $cancelled);

        return back()->with('success', $cancelled ? 'Session cancelled; it no longer counts toward attendance.' : 'Session restored.');
    }

    /** Student: own attendance per course. */
    public function mine(Request $request): Response
    {
        $student = $request->user()->student;
        abort_if($student === null, 403, 'No student profile is linked to this account.');

        return Inertia::render('Attendance/Mine', ['summary' => $this->attendance->studentSummary($student)]);
    }
}
