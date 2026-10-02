<?php

namespace App\Http\Controllers;

use App\Dto\UniversityStructure\CourseListFilters;
use App\Http\Controllers\Api\GradeController;
use App\Http\Requests\StoreCoursePrerequisiteRequest;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Http\Resources\CourseResource;
use App\Http\Resources\DepartmentResource;
use App\Http\Resources\FacultyResource;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Program;
use App\Services\CourseService;
use App\Services\GradingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Course catalog screens (module 9.7).
 *
 * Offerings and sections are not part of this controller: they depend on
 * lecturers, rooms and the timetable (9.3 / 9.8 / 9.10).
 */
class CourseController extends Controller
{
    public function __construct(
        private readonly CourseService $courses,
        private readonly GradingService $grading,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Course::class);

        $filters = CourseListFilters::fromInput($request->query());

        $courses = $this->courses->paginate($filters)
            ->withQueryString()
            ->appends($filters->toQueryString());

        return Inertia::render('Courses/Index', [
            'courses' => CourseResource::collection($courses),
            ...$this->lookups(),
            'filters' => [
                'search' => $filters->search,
                'faculty_id' => $filters->facultyId,
                'department_id' => $filters->departmentId,
                'program_id' => $filters->programId,
                'status' => $filters->status,
                'course_level' => $filters->level,
            ],
        ]);
    }

    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $this->authorize('create', Course::class);

        $course = $this->courses->create($request->validated());

        return redirect()
            ->route('courses.edit', $course)
            ->with('success', 'Course created. You can now add prerequisites.');
    }

    public function edit(Course $course): Response
    {
        $this->authorize('update', $course);

        $course->load(['department.faculty:id,code,name', 'prerequisites', 'programs']);

        return Inertia::render('Courses/Edit', [
            // `resolve()` so the page reads `props.course.code`, not `.data.code`.
            'course' => (new CourseResource($course))->resolve(),
            // Candidates for a new prerequisite: any non-archived course except
            // itself and the ones already chosen. Cycles are rejected by the service.
            'prerequisiteOptions' => Course::query()
                ->where('status', '!=', Course::STATUS_ARCHIVED)
                ->whereKeyNot($course->getKey())
                ->whereNotIn('id', $course->prerequisites->modelKeys())
                ->orderBy('code')
                ->get(['id', 'code', 'name'])
                ->map(fn (Course $option) => ['id' => $option->id, 'code' => $option->code, 'name' => $option->name])
                ->values(),
            // Component weights of the course grade (module 9.14).
            'gradingConfig' => GradeController::configPayload($this->grading->configFor($course)),
            ...$this->lookups(),
        ]);
    }

    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $this->authorize('update', $course);

        $this->courses->update($course, $request->validated());

        return back()->with('success', 'Course updated.');
    }

    public function archive(Course $course): RedirectResponse
    {
        $this->authorize('archive', $course);

        $this->courses->archive($course);

        return back()->with('success', 'Course archived.');
    }

    public function reactivate(Course $course): RedirectResponse
    {
        $this->authorize('archive', $course);

        $this->courses->reactivate($course);

        return back()->with('success', 'Course reactivated.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $this->authorize('delete', $course);

        $this->courses->delete($course);

        return redirect()
            ->route('courses.index')
            ->with('success', 'Course deleted.');
    }

    public function addPrerequisite(StoreCoursePrerequisiteRequest $request, Course $course): RedirectResponse
    {
        $this->authorize('update', $course);

        $this->courses->addPrerequisite(
            $course,
            Course::query()->findOrFail($request->validated('prerequisite_course_id')),
            (bool) $request->validated('is_strict', true),
        );

        return back()->with('success', 'Prerequisite added.');
    }

    public function removePrerequisite(Course $course, Course $prerequisite): RedirectResponse
    {
        $this->authorize('update', $course);
        abort_unless(
            $course->prerequisites()->whereKey($prerequisite->getKey())->exists(),
            404,
            'That course is not a prerequisite.',
        );

        $this->courses->removePrerequisite($course, $prerequisite);

        return back()->with('success', 'Prerequisite removed.');
    }

    /**
     * Faculty, department and program options for the filter bar and forms.
     * Archived units are excluded: a course cannot be created under them.
     *
     * @return array<string, mixed>
     */
    private function lookups(): array
    {
        return [
            'faculties' => FacultyResource::collection(
                Faculty::query()->where('is_active', true)->orderBy('name')->get()
            ),
            'departments' => DepartmentResource::collection(
                Department::query()->where('is_active', true)->orderBy('name')->get()
            ),
            'programs' => Program::query()->orderBy('code')->get(['id', 'code', 'name'])
                ->map(fn (Program $program) => ['id' => $program->id, 'code' => $program->code, 'name' => $program->name])
                ->values(),
            'levels' => Course::LEVELS,
            'statuses' => Course::STATUSES,
        ];
    }
}
