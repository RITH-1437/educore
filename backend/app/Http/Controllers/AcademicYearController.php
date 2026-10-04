<?php

namespace App\Http\Controllers;

use App\Dto\AcademicYear\AcademicYearListFilters;
use App\Enums\AcademicYearStatus;
use App\Enums\SemesterStatus;
use App\Http\Requests\ChangeAcademicYearStatusRequest;
use App\Http\Requests\ChangeSemesterStatusRequest;
use App\Http\Requests\StoreAcademicYearRequest;
use App\Http\Requests\StoreSemesterRequest;
use App\Http\Requests\UpdateAcademicYearRequest;
use App\Http\Resources\AcademicYearResource;
use App\Http\Resources\SemesterResource;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Services\AcademicYearService;
use App\Services\SemesterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Academic year and semester screens.
 *
 * Semesters are a genuine child collection, so they are managed from the
 * academic year detail screen.
 */
class AcademicYearController extends Controller
{
    public function __construct(
        private readonly AcademicYearService $academicYears,
        private readonly SemesterService $semesters,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AcademicYear::class);

        $filters = AcademicYearListFilters::fromInput($request->query());

        $academicYears = $this->academicYears->paginate($filters)
            ->withQueryString()
            ->appends($filters->toQueryString());

        return Inertia::render('AcademicYears/Index', [
            'academicYears' => AcademicYearResource::collection($academicYears),
            'filters' => ['search' => $filters->search, 'status' => $filters->status],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', AcademicYear::class);

        return Inertia::render('AcademicYears/Create');
    }

    public function store(StoreAcademicYearRequest $request): RedirectResponse
    {
        $this->authorize('create', AcademicYear::class);

        $academicYear = $this->academicYears->create($request->validated());

        return redirect()
            ->route('academic-years.edit', $academicYear)
            ->with('success', 'Academic year created.');
    }

    public function edit(AcademicYear $academicYear): Response
    {
        $this->authorize('update', $academicYear);

        return Inertia::render('AcademicYears/Edit', [
            // `resolve()` instead of the resource instance: Inertia treats a bare
            // `JsonResource` as a `Responsable`, so it would nest the payload under
            // `data` and the page would read `props.academicYear.data.code`.
            'academicYear' => (new AcademicYearResource($academicYear))->resolve(),
            // Resolved for the same reason: the page iterates a plain list.
            'semesters' => SemesterResource::collection(
                $academicYear->semesters()->get()
            )->resolve(),
        ]);
    }

    public function update(UpdateAcademicYearRequest $request, AcademicYear $academicYear): RedirectResponse
    {
        $this->authorize('update', $academicYear);

        $this->academicYears->update($academicYear, $request->validated());

        return redirect()
            ->route('academic-years.index')
            ->with('success', 'Academic year updated.');
    }

    public function destroy(AcademicYear $academicYear): RedirectResponse
    {
        $this->authorize('delete', $academicYear);

        $this->academicYears->delete($academicYear);

        return redirect()
            ->route('academic-years.index')
            ->with('success', 'Academic year deleted.');
    }

    public function changeStatus(ChangeAcademicYearStatusRequest $request, AcademicYear $academicYear): RedirectResponse
    {
        $this->authorize('update', $academicYear);

        $this->academicYears->changeStatus(
            $academicYear,
            AcademicYearStatus::from($request->validated('status')),
        );

        return back()->with('success', 'Academic year status updated.');
    }

    public function makeCurrent(AcademicYear $academicYear): RedirectResponse
    {
        $this->authorize('update', $academicYear);

        $this->academicYears->makeCurrent($academicYear);

        return back()->with('success', 'Current academic year updated.');
    }

    public function storeSemester(StoreSemesterRequest $request, AcademicYear $academicYear): RedirectResponse
    {
        $this->authorize('create', Semester::class);

        $this->semesters->create($academicYear, $request->validated());

        return back()->with('success', 'Semester added.');
    }

    public function changeSemesterStatus(
        ChangeSemesterStatusRequest $request,
        AcademicYear $academicYear,
        Semester $semester,
    ): RedirectResponse {
        $this->authorize('update', $semester);
        $this->assertSemesterBelongsToYear($academicYear, $semester);

        $this->semesters->changeStatus($semester, SemesterStatus::from($request->validated('status')));

        return back()->with('success', 'Semester status updated.');
    }

    public function destroySemester(AcademicYear $academicYear, Semester $semester): RedirectResponse
    {
        $this->authorize('delete', $semester);
        $this->assertSemesterBelongsToYear($academicYear, $semester);

        $this->semesters->delete($semester);

        return back()->with('success', 'Semester deleted.');
    }

    /**
     * A nested semester must belong to the academic year in the URL.
     */
    private function assertSemesterBelongsToYear(AcademicYear $academicYear, Semester $semester): void
    {
        abort_unless(
            $semester->academic_year_id === $academicYear->getKey(),
            404,
            'Semester not found in this academic year.',
        );
    }
}
