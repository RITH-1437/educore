<?php

namespace App\Http\Controllers;

use App\Dto\UniversityStructure\FacultyListFilters;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\StoreFacultyRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Requests\UpdateFacultyRequest;
use App\Http\Resources\DepartmentResource;
use App\Http\Resources\FacultyResource;
use App\Http\Resources\UniversityResource;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\University;
use App\Services\DepartmentService;
use App\Services\FacultyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Faculty and department screens.
 *
 * Departments are a genuine child collection, so they are managed from the
 * faculty list screen rather than a separate top-level area.
 */
class FacultyController extends Controller
{
    public function __construct(
        private readonly FacultyService $faculties,
        private readonly DepartmentService $departments,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Faculty::class);

        $filters = FacultyListFilters::fromInput($request->query());

        $faculties = $this->faculties->paginate($filters)
            ->withQueryString()
            ->appends($filters->toQueryString());

        // Only the departments of the faculties actually on screen, so the
        // expandable tree never needs a second round trip.
        $facultyIds = $faculties->getCollection()->modelKeys();

        return Inertia::render('Faculties/Index', [
            'faculties' => FacultyResource::collection($faculties),
            'departments' => DepartmentResource::collection(
                $this->departments->listForFaculties($facultyIds)
            ),
            'universities' => UniversityResource::collection(
                University::query()->orderBy('name')->get()
            ),
            'filters' => [
                'search' => $filters->search,
                'university_id' => $filters->universityId,
                'is_active' => $filters->isActive,
            ],
        ]);
    }

    public function store(StoreFacultyRequest $request): RedirectResponse
    {
        $this->authorize('create', Faculty::class);

        $this->faculties->create($request->validated());

        return redirect()
            ->route('faculties.index')
            ->with('success', 'Faculty created.');
    }

    public function edit(Faculty $faculty): Response
    {
        $this->authorize('update', $faculty);

        return Inertia::render('Faculties/Edit', [
            // `resolve()` instead of the resource instance: Inertia treats a bare
            // `JsonResource` as a `Responsable`, so it would nest the payload under
            // `data` and the page would read `props.faculty.data.code`.
            'faculty' => (new FacultyResource(
                $faculty->load('university:id,code,name')->loadCount('departments')
            ))->resolve(),
            'universities' => UniversityResource::collection(
                University::query()->orderBy('name')->get()
            ),
        ]);
    }

    public function update(UpdateFacultyRequest $request, Faculty $faculty): RedirectResponse
    {
        $this->authorize('update', $faculty);

        $this->faculties->update($faculty, $request->validated());

        return redirect()
            ->route('faculties.index')
            ->with('success', 'Faculty updated.');
    }

    public function archive(Faculty $faculty): RedirectResponse
    {
        $this->authorize('archive', $faculty);

        $this->faculties->archive($faculty);

        return back()->with('success', 'Faculty archived.');
    }

    public function reactivate(Faculty $faculty): RedirectResponse
    {
        $this->authorize('archive', $faculty);

        $this->faculties->reactivate($faculty);

        return back()->with('success', 'Faculty reactivated.');
    }

    public function destroy(Faculty $faculty): RedirectResponse
    {
        $this->authorize('delete', $faculty);

        $this->faculties->delete($faculty);

        return redirect()
            ->route('faculties.index')
            ->with('success', 'Faculty deleted.');
    }

    public function storeDepartment(StoreDepartmentRequest $request, Faculty $faculty): RedirectResponse
    {
        $this->authorize('create', Department::class);

        $this->departments->create([
            ...$request->safe()->except('faculty_id'),
            'faculty_id' => $faculty->getKey(),
        ]);

        return back()->with('success', 'Department added.');
    }

    public function updateDepartment(
        UpdateDepartmentRequest $request,
        Faculty $faculty,
        Department $department,
    ): RedirectResponse {
        $this->authorize('update', $department);
        $this->assertDepartmentBelongsToFaculty($faculty, $department);

        $this->departments->update($department, [
            ...$request->validated(),
            'faculty_id' => $faculty->getKey(),
        ]);

        return back()->with('success', 'Department updated.');
    }

    public function archiveDepartment(Faculty $faculty, Department $department): RedirectResponse
    {
        $this->authorize('archive', $department);
        $this->assertDepartmentBelongsToFaculty($faculty, $department);

        $this->departments->archive($department);

        return back()->with('success', 'Department archived.');
    }

    public function reactivateDepartment(Faculty $faculty, Department $department): RedirectResponse
    {
        $this->authorize('archive', $department);
        $this->assertDepartmentBelongsToFaculty($faculty, $department);

        $this->departments->reactivate($department);

        return back()->with('success', 'Department reactivated.');
    }

    public function destroyDepartment(Faculty $faculty, Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);
        $this->assertDepartmentBelongsToFaculty($faculty, $department);

        $this->departments->delete($department);

        return redirect()
            ->route('faculties.index')
            ->with('success', 'Department deleted.');
    }

    /**
     * A nested department must belong to the faculty in the URL.
     */
    private function assertDepartmentBelongsToFaculty(Faculty $faculty, Department $department): void
    {
        abort_unless(
            $department->faculty_id === $faculty->getKey(),
            404,
            'Department not found in this faculty.',
        );
    }
}
