<?php

namespace App\Http\Controllers;

use App\Dto\People\LecturerListFilters;
use App\Enums\Role as RoleSlug;
use App\Http\Requests\StoreLecturerRequest;
use App\Http\Requests\UpdateLecturerRequest;
use App\Http\Resources\DepartmentResource;
use App\Http\Resources\LecturerResource;
use App\Http\Resources\SectionResource;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\User;
use App\Services\LecturerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lecturer screens (module 9.3).
 *
 * Teaching assignments (sections) are not managed here yet — they arrive with
 * 9.8 Class / Section.
 */
class LecturerController extends Controller
{
    public function __construct(
        private readonly LecturerService $lecturers,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Lecturer::class);

        $filters = LecturerListFilters::fromInput($request->query());

        $lecturers = $this->lecturers->paginate($filters, $request->user())
            ->withQueryString()
            ->appends($filters->toQueryString());

        return Inertia::render('Lecturers/Index', [
            'lecturers' => LecturerResource::collection($lecturers),
            ...$this->lookups(),
            // Lecturer accounts created in Users management that have no
            // profile yet — offered as "link existing account" when creating,
            // so only users who may create a lecturer receive them.
            'unlinkedAccounts' => $request->user()->cannot('create', Lecturer::class) ? [] : User::query()
                ->whereHas('role', fn ($query) => $query->where('slug', RoleSlug::Lecturer->value))
                ->whereDoesntHave('lecturer')
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email])
                ->values(),
            'filters' => [
                'search' => $filters->search,
                'department_id' => $filters->departmentId,
                'employment_type' => $filters->employmentType,
                'is_active' => $filters->isActive,
            ],
        ]);
    }

    public function store(StoreLecturerRequest $request): RedirectResponse
    {
        $this->authorize('create', Lecturer::class);

        $this->lecturers->create($request->validated());

        return redirect()
            ->route('lecturers.index')
            ->with('success', 'Lecturer created.');
    }

    public function edit(Lecturer $lecturer): Response
    {
        $this->authorize('update', $lecturer);

        return Inertia::render('Lecturers/Edit', [
            // `resolve()` so the page reads `props.lecturer.staff_number`, not `.data.…`.
            'lecturer' => (new LecturerResource($lecturer->load(['user', 'department:id,code,name'])))->resolve(),
            'sections' => SectionResource::collection(
                $lecturer->sections()->with(['offering.course:id,code,name,credits', 'offering.semester.academicYear:id,code'])->get()
            )->resolve(),
            ...$this->lookups(),
        ]);
    }

    public function update(UpdateLecturerRequest $request, Lecturer $lecturer): RedirectResponse
    {
        $this->authorize('update', $lecturer);

        $this->lecturers->update($lecturer, $request->validated());

        return redirect()
            ->route('lecturers.index')
            ->with('success', 'Lecturer updated.');
    }

    public function deactivate(Lecturer $lecturer): RedirectResponse
    {
        $this->authorize('deactivate', $lecturer);

        $this->lecturers->deactivate($lecturer);

        return back()->with('success', 'Lecturer deactivated. They can no longer sign in.');
    }

    public function reactivate(Lecturer $lecturer): RedirectResponse
    {
        $this->authorize('deactivate', $lecturer);

        $this->lecturers->reactivate($lecturer);

        return back()->with('success', 'Lecturer reactivated. They can sign in again.');
    }

    public function destroy(Lecturer $lecturer): RedirectResponse
    {
        $this->authorize('delete', $lecturer);

        $this->lecturers->delete($lecturer);

        return redirect()
            ->route('lecturers.index')
            ->with('success', 'Lecturer profile deleted. The account was kept but can no longer sign in.');
    }

    /**
     * @return array<string, mixed>
     */
    /** Filter / form options; a Department Admin only gets their own department's. */
    private function lookups(): array
    {
        return [
            'departments' => DepartmentResource::collection(
                Department::query()->visibleTo(request()->user())->where('is_active', true)->orderBy('name')->get()
            ),
            'employmentTypes' => Lecturer::EMPLOYMENT_TYPES,
        ];
    }
}
