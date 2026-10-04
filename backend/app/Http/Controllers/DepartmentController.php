<?php

namespace App\Http\Controllers;

use App\Dto\UniversityStructure\DepartmentListFilters;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Http\Resources\UniversityResource;
use App\Models\Department;
use App\Models\University;
use App\Services\DepartmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Department management screens (`docs/39_Department-Only-Structure-Report.md`).
 * Departments sit directly under the university; Super Admin and University
 * Admin manage them, a Department Admin reads their own (`DepartmentPolicy`).
 */
class DepartmentController extends Controller
{
    public function __construct(
        private readonly DepartmentService $departments,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Department::class);

        $filters = DepartmentListFilters::fromInput($request->query());

        $departments = $this->departments->paginate($filters, $request->user())
            ->withQueryString()
            ->appends($filters->toQueryString());

        return Inertia::render('Departments/Index', [
            'departments' => DepartmentResource::collection($departments),
            'universities' => $this->universities(),
            'filters' => [
                'search' => $filters->search,
                'university_id' => $filters->universityId,
                'is_active' => $filters->isActive,
            ],
        ]);
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $this->authorize('create', Department::class);

        $this->departments->create($request->validated());

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department created.');
    }

    public function edit(Department $department): Response
    {
        $this->authorize('update', $department);

        return Inertia::render('Departments/Edit', [
            // `resolve()`: a bare `JsonResource` prop would be nested under `data`.
            'department' => (new DepartmentResource(
                $department->load('university:id,code,name')->loadCount('programs')
            ))->resolve(),
            'universities' => $this->universities(),
        ]);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $this->authorize('update', $department);

        $this->departments->update($department, $request->validated());

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department updated.');
    }

    public function archive(Department $department): RedirectResponse
    {
        $this->authorize('archive', $department);

        $this->departments->archive($department);

        return back()->with('success', 'Department archived.');
    }

    public function reactivate(Department $department): RedirectResponse
    {
        $this->authorize('archive', $department);

        $this->departments->reactivate($department);

        return back()->with('success', 'Department reactivated.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);

        $this->departments->delete($department);

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department deleted.');
    }

    /**
     * University options for the department forms and filter, as a plain list.
     *
     * @return list<array<string, mixed>>
     */
    private function universities(): array
    {
        return UniversityResource::collection(University::query()->orderByDesc('is_current')->orderBy('name')->get())->resolve();
    }
}
