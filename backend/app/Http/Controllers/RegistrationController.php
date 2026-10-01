<?php

namespace App\Http\Controllers;

use App\Http\Resources\EnrollmentResource;
use App\Models\Enrollment;
use App\Models\Section;
use App\Services\EnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Student self-service course registration (module 9.9). The student is always
 * the signed-in account's own profile — never taken from the request.
 */
class RegistrationController extends Controller
{
    public function __construct(
        private readonly EnrollmentService $enrollments,
    ) {}

    public function index(Request $request): Response
    {
        $student = $request->user()->student;
        abort_if($student === null, 403, 'No student profile is linked to this account.');

        $enrolledSectionIds = $student->enrollments()->whereIn('status', Enrollment::OPEN_STATUSES)->pluck('section_id')->all();

        return Inertia::render('Registration/Index', [
            'student' => ['id' => $student->id, 'student_number' => $student->student_number, 'full_name' => $student->fullName(), 'status' => $student->status],
            'enrollments' => EnrollmentResource::collection(
                $student->enrollments()->with(['section.offering.course:id,code,name,credits', 'semester.academicYear:id,code'])->orderByDesc('enrolled_at')->get()
            )->resolve(),
            'maxCredits' => (float) config('academics.max_semester_credits', 24),
            'sections' => Section::query()
                ->with(['offering.course.prerequisites:id,code', 'offering.semester', 'lecturers'])
                ->whereIn('status', ['open', 'active'])
                ->whereHas('offering', fn ($q) => $q->where('status', 'open')->whereHas('semester', fn ($s) => $s->where('status', 'open')))
                ->get()
                ->map(function (Section $section) use ($student, $enrolledSectionIds) {
                    $course = $section->offering->course;

                    return [
                        'id' => $section->id,
                        'code' => $section->code,
                        'course' => ['code' => $course->code, 'name' => $course->name, 'credits' => (float) $course->credits],
                        'lecturer' => $section->lecturers->firstWhere('pivot.role', 'primary')?->fullName(),
                        'seats' => $this->enrollments->openSeats($section),
                        'registration_open' => $this->enrollments->isRegistrationOpen($section),
                        'missing_prerequisites' => $this->enrollments->missingPrerequisites($student, $course),
                        'enrolled' => in_array($section->id, $enrolledSectionIds, true),
                    ];
                })
                ->sortBy(fn ($row) => $row['course']['code'].$row['code'])->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $student = $request->user()->student;
        abort_if($student === null, 403, 'No student profile is linked to this account.');

        $data = $request->validate(['section_id' => ['required', 'integer', 'exists:sections,id']]);

        $this->enrollments->enroll($student, Section::query()->findOrFail($data['section_id']));

        return back()->with('success', 'You are enrolled.');
    }

    public function drop(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $this->authorize('drop', $enrollment);

        $this->enrollments->drop($enrollment);

        return back()->with('success', 'Enrollment dropped.');
    }
}
