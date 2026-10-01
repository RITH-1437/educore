<?php

namespace App\Http\Controllers;

use App\Enums\SemesterStatus;
use App\Http\Requests\AssignSectionLecturerRequest;
use App\Http\Requests\StoreCourseOfferingRequest;
use App\Http\Requests\StoreSectionRequest;
use App\Http\Requests\UpdateCourseOfferingRequest;
use App\Http\Requests\UpdateSectionRequest;
use App\Http\Resources\CourseOfferingResource;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Lecturer;
use App\Models\Room;
use App\Models\ScheduleEntry;
use App\Models\Section;
use App\Models\Semester;
use App\Services\CourseOfferingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Offerings & sections screens (module 9.8): one list page and one manage page
 * per offering (its sections and their lecturers).
 */
class CourseOfferingController extends Controller
{
    public function __construct(
        private readonly CourseOfferingService $offerings,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CourseOffering::class);

        $filters = [
            'search' => $request->query('search'),
            'semester_id' => $request->integer('semester_id') ?: null,
            'status' => in_array($request->query('status'), CourseOffering::STATUSES, true) ? $request->query('status') : null,
        ];

        return Inertia::render('Offerings/Index', [
            'offerings' => CourseOfferingResource::collection(
                $this->offerings->paginate($filters)->withQueryString()
            ),
            'semesters' => $this->semesterOptions(),
            'courses' => Course::query()->where('status', Course::STATUS_ACTIVE)->orderBy('code')->get(['id', 'code', 'name'])
                ->map(fn (Course $course) => ['id' => $course->id, 'code' => $course->code, 'name' => $course->name])->values(),
            'statuses' => CourseOffering::STATUSES,
            'filters' => $filters,
        ]);
    }

    public function store(StoreCourseOfferingRequest $request): RedirectResponse
    {
        $this->authorize('create', CourseOffering::class);

        $offering = $this->offerings->create($request->validated());

        return redirect()->route('offerings.show', $offering)->with('success', 'Offering created. Add its sections.');
    }

    public function show(CourseOffering $offering): Response
    {
        $this->authorize('view', $offering);

        $offering->load([
            'course:id,code,name,credits',
            'semester.academicYear:id,code,name',
            'sections' => fn ($query) => $query->select('sections.*')
                ->addSelect(['enrolled_count' => DB::table('enrollments')
                    ->selectRaw('count(*)')
                    ->whereColumn('section_id', 'sections.id')
                    ->whereNull('deleted_at')
                    ->whereIn('status', ['pending', 'confirmed'])])
                ->with(['lecturers', 'scheduleEntries.room']),
        ]);

        return Inertia::render('Offerings/Show', [
            'offering' => (new CourseOfferingResource($offering))->resolve(),
            'lecturers' => Lecturer::query()->where('is_active', true)->with('department:id,code')->orderBy('last_name')->get()
                ->map(fn (Lecturer $lecturer) => [
                    'id' => $lecturer->id,
                    'label' => $lecturer->fullName().' ('.$lecturer->staff_number.', '.$lecturer->department?->code.')',
                ])->values(),
            'rooms' => Room::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name', 'capacity'])
                ->map(fn (Room $room) => ['id' => $room->id, 'label' => $room->code.' — '.$room->name.' ('.$room->capacity.' seats)'])->values(),
            'days' => ScheduleEntry::DAYS,
            'statuses' => CourseOffering::STATUSES,
            'sectionStatuses' => Section::STATUSES,
            'lecturerRoles' => Section::LECTURER_ROLES,
        ]);
    }

    public function update(UpdateCourseOfferingRequest $request, CourseOffering $offering): RedirectResponse
    {
        $this->authorize('update', $offering);

        $this->offerings->update($offering, $request->validated());

        return back()->with('success', 'Offering updated.');
    }

    public function destroy(CourseOffering $offering): RedirectResponse
    {
        $this->authorize('delete', $offering);

        $this->offerings->delete($offering);

        return redirect()->route('offerings.index')->with('success', 'Offering deleted.');
    }

    public function storeSection(StoreSectionRequest $request, CourseOffering $offering): RedirectResponse
    {
        $this->authorize('update', $offering);

        $this->offerings->createSection($offering, $request->validated());

        return back()->with('success', 'Section added.');
    }

    public function updateSection(UpdateSectionRequest $request, Section $section): RedirectResponse
    {
        $this->authorize('update', $section->offering);

        $this->offerings->updateSection($section, $request->validated());

        return back()->with('success', 'Section updated.');
    }

    public function destroySection(Section $section): RedirectResponse
    {
        $this->authorize('update', $section->offering);

        $this->offerings->deleteSection($section);

        return back()->with('success', 'Section deleted.');
    }

    public function assignLecturer(AssignSectionLecturerRequest $request, Section $section): RedirectResponse
    {
        $this->authorize('update', $section->offering);

        $this->offerings->assignLecturer(
            $section,
            Lecturer::query()->findOrFail($request->validated('lecturer_id')),
            $request->validated('role', 'primary'),
        );

        return back()->with('success', 'Lecturer assigned.');
    }

    public function removeLecturer(Section $section, Lecturer $lecturer): RedirectResponse
    {
        $this->authorize('update', $section->offering);
        abort_unless($section->lecturers()->whereKey($lecturer->getKey())->exists(), 404, 'Lecturer is not assigned to this section.');

        $this->offerings->removeLecturer($section, $lecturer);

        return back()->with('success', 'Lecturer removed from the section.');
    }

    /**
     * Semesters for the filter and the create form; completed ones are marked
     * so the UI can disable them (the service refuses them anyway).
     *
     * @return list<array<string, mixed>>
     */
    private function semesterOptions(): array
    {
        return Semester::query()
            ->with('academicYear:id,code')
            ->orderByDesc('academic_year_id')
            ->orderBy('sequence')
            ->get()
            ->map(fn (Semester $semester) => [
                'id' => $semester->id,
                'label' => ($semester->academicYear?->code ?? '').' · '.$semester->name,
                'completed' => $semester->status === SemesterStatus::Completed,
            ])
            ->values()
            ->all();
    }
}
