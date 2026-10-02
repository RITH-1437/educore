<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Services\EnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Staff enrollment management (module 9.9). Admin enrollments run through the
 * same `EnrollmentService::enroll()` as student self-service.
 */
class EnrollmentController extends Controller
{
    public function __construct(
        private readonly EnrollmentService $enrollments,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Enrollment::class);

        $filters = [
            'search' => $request->query('search'),
            'semester_id' => $request->integer('semester_id') ?: null,
            'section_id' => $request->integer('section_id') ?: null,
            'status' => in_array($request->query('status'), Enrollment::STATUSES, true) ? $request->query('status') : null,
        ];

        return Inertia::render('Enrollments/Index', [
            'enrollments' => EnrollmentResource::collection($this->enrollments->paginate($filters, $request->user())->withQueryString()),
            'semesters' => Semester::query()->with('academicYear:id,code')->orderByDesc('academic_year_id')->orderBy('sequence')->get()
                ->map(fn (Semester $semester) => ['id' => $semester->id, 'label' => $semester->academicYear?->code.' · '.$semester->name])->values(),
            // Sections currently open for registration, with seats left.
            'openSections' => Section::query()
                ->with('offering.course:id,code,name,credits', 'offering.semester')
                ->whereIn('status', ['open', 'active'])
                ->whereHas('offering', fn ($q) => $q->where('status', 'open')->whereHas('semester', fn ($s) => $s->where('status', 'open')))
                ->get()
                ->map(fn (Section $section) => [
                    'id' => $section->id,
                    'label' => $section->offering->course->code.' '.$section->code.' — '.$section->offering->course->name,
                    'seats' => $this->enrollments->openSeats($section),
                ])
                ->sortBy('label')->values(),
            'students' => Student::query()->where('status', Student::STATUS_ACTIVE)->orderBy('student_number')->get(['id', 'student_number', 'first_name', 'last_name'])
                ->map(fn (Student $student) => ['id' => $student->id, 'label' => $student->student_number.' — '.$student->fullName()])->values(),
            'statuses' => Enrollment::STATUSES,
            'filters' => $filters,
        ]);
    }

    public function store(StoreEnrollmentRequest $request): RedirectResponse
    {
        $this->authorize('create', Enrollment::class);

        $this->enrollments->enroll(
            Student::query()->findOrFail($request->validated('student_id')),
            Section::query()->findOrFail($request->validated('section_id')),
        );

        return back()->with('success', 'Student enrolled.');
    }

    public function drop(Enrollment $enrollment): RedirectResponse
    {
        $this->authorize('drop', $enrollment);

        $result = $this->enrollments->drop($enrollment);

        return back()->with('success', $result->status === Enrollment::STATUS_WITHDRAWN ? 'Enrollment withdrawn (records kept).' : 'Enrollment dropped.');
    }

    public function complete(Enrollment $enrollment): RedirectResponse
    {
        $this->authorize('complete', $enrollment);

        $this->enrollments->complete($enrollment);

        return back()->with('success', 'Enrollment marked completed.');
    }
}
