<?php

namespace App\Http\Controllers;

use App\Dto\UniversityStructure\ProgramListFilters;
use App\Http\Requests\StoreProgramCourseRequest;
use App\Http\Requests\StoreProgramRequest;
use App\Http\Requests\UpdateProgramCourseRequest;
use App\Http\Requests\UpdateProgramRequest;
use App\Http\Resources\DepartmentResource;
use App\Http\Resources\FacultyResource;
use App\Http\Resources\ProgramResource;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Program;
use App\Services\ProgramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Program screens (module 9.5).
 *
 * Programs are a top-level admin area of their own; each one belongs to exactly
 * one department, chosen through a faculty → department cascade in the UI.
 */
class ProgramController extends Controller
{
    public function __construct(
        private readonly ProgramService $programs,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Program::class);

        $filters = ProgramListFilters::fromInput($request->query());

        $programs = $this->programs->paginate($filters, $request->user())
            ->withQueryString()
            ->appends($filters->toQueryString());

        return Inertia::render('Programs/Index', [
            'programs' => ProgramResource::collection($programs),
            ...$this->lookups(),
            'degreeLevels' => Program::DEGREE_LEVELS,
            'filters' => [
                'search' => $filters->search,
                'faculty_id' => $filters->facultyId,
                'department_id' => $filters->departmentId,
                'degree_level' => $filters->degreeLevel,
                'is_active' => $filters->isActive,
            ],
        ]);
    }

    public function store(StoreProgramRequest $request): RedirectResponse
    {
        $this->authorize('create', Program::class);

        $this->programs->create($request->validated());

        return redirect()
            ->route('programs.index')
            ->with('success', 'Program created.');
    }

    public function edit(Program $program): Response
    {
        $this->authorize('update', $program);

        return Inertia::render('Programs/Edit', [
            // `resolve()` so the page reads `props.program.code`, not `.data.code`.
            'program' => (new ProgramResource($program->load(['department.faculty:id,code,name', 'courses'])))->resolve(),
            // Courses that can still join the curriculum: not archived, not already in it.
            'availableCourses' => Course::query()
                ->where('status', '!=', Course::STATUS_ARCHIVED)
                ->whereNotIn('id', $program->courses->modelKeys())
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'credits'])
                ->map(fn (Course $course) => [
                    'id' => $course->id,
                    'code' => $course->code,
                    'name' => $course->name,
                    'credits' => (float) $course->credits,
                ])
                ->values(),
            ...$this->lookups(),
            'degreeLevels' => Program::DEGREE_LEVELS,
        ]);
    }

    public function addCourse(StoreProgramCourseRequest $request, Program $program): RedirectResponse
    {
        $this->authorize('update', $program);

        $this->programs->addCourse($program, Course::query()->findOrFail($request->validated('course_id')), $request->validated());

        return back()->with('success', 'Course added to the curriculum.');
    }

    public function updateCourse(UpdateProgramCourseRequest $request, Program $program, Course $course): RedirectResponse
    {
        $this->authorize('update', $program);
        abort_unless($this->programs->isInCurriculum($program, $course), 404, 'Course is not in this curriculum.');

        $this->programs->updateCourse($program, $course, $request->validated());

        return back()->with('success', 'Curriculum placement updated.');
    }

    public function removeCourse(Program $program, Course $course): RedirectResponse
    {
        $this->authorize('update', $program);
        abort_unless($this->programs->isInCurriculum($program, $course), 404, 'Course is not in this curriculum.');

        $this->programs->removeCourse($program, $course);

        return back()->with('success', 'Course removed from the curriculum.');
    }

    public function update(UpdateProgramRequest $request, Program $program): RedirectResponse
    {
        $this->authorize('update', $program);

        $this->programs->update($program, $request->validated());

        return redirect()
            ->route('programs.index')
            ->with('success', 'Program updated.');
    }

    public function archive(Program $program): RedirectResponse
    {
        $this->authorize('archive', $program);

        $this->programs->archive($program);

        return back()->with('success', 'Program archived.');
    }

    public function reactivate(Program $program): RedirectResponse
    {
        $this->authorize('archive', $program);

        $this->programs->reactivate($program);

        return back()->with('success', 'Program reactivated.');
    }

    public function destroy(Program $program): RedirectResponse
    {
        $this->authorize('delete', $program);

        $this->programs->delete($program);

        return redirect()
            ->route('programs.index')
            ->with('success', 'Program deleted.');
    }

    /**
     * Faculty and department options for the filter bar and the form cascade.
     * Archived units are excluded: a program cannot be created under them.
     *
     * @return array<string, mixed>
     */
    /** Filter / form options; a Faculty Admin only gets their own faculty's. */
    private function lookups(): array
    {
        return [
            'faculties' => FacultyResource::collection(
                Faculty::query()->visibleTo(request()->user())->where('is_active', true)->orderBy('name')->get()
            ),
            'departments' => DepartmentResource::collection(
                Department::query()->visibleTo(request()->user())->where('is_active', true)->orderBy('name')->get()
            ),
        ];
    }
}
