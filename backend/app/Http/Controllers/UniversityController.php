<?php

namespace App\Http\Controllers;

use App\Dto\UniversityStructure\UniversityListFilters;
use App\Http\Requests\StoreUniversityRequest;
use App\Http\Requests\UpdateUniversityRequest;
use App\Http\Resources\UniversityResource;
use App\Models\University;
use App\Services\UniversityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * University screens.
 *
 * The university is a single platform-wide record, so this is a compact
 * list plus an edit screen — no separate create page is needed when there is a
 * single tenant.
 */
class UniversityController extends Controller
{
    public function __construct(
        private readonly UniversityService $universities,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', University::class);

        $filters = UniversityListFilters::fromInput($request->query());

        $universities = $this->universities->paginate($filters)
            ->withQueryString()
            ->appends($filters->toQueryString());

        return Inertia::render('Universities/Index', [
            'universities' => UniversityResource::collection($universities),
            'filters' => [
                'search' => $filters->search,
                'status' => $filters->isCurrent === null ? '' : ($filters->isCurrent ? 'current' : 'other'),
            ],
        ]);
    }

    public function store(StoreUniversityRequest $request): RedirectResponse
    {
        $this->authorize('create', University::class);

        $this->universities->create($request->validated());

        return redirect()
            ->route('universities.index')
            ->with('success', 'University created.');
    }

    public function edit(University $university): Response
    {
        $this->authorize('update', $university);

        return Inertia::render('Universities/Edit', [
            // `resolve()` instead of the resource instance: Inertia treats a bare
            // `JsonResource` as a `Responsable`, so it would nest the payload under
            // `data` and the page would read `props.university.data.code`.
            'university' => (new UniversityResource($university->loadCount('departments')))->resolve(),
        ]);
    }

    public function update(UpdateUniversityRequest $request, University $university): RedirectResponse
    {
        $this->authorize('update', $university);

        $this->universities->update($university, $request->validated());

        return redirect()
            ->route('universities.index')
            ->with('success', 'University updated.');
    }

    public function makeCurrent(University $university): RedirectResponse
    {
        $this->authorize('update', $university);

        $this->universities->makeCurrent($university);

        return back()->with('success', 'Current university updated.');
    }

    public function destroy(University $university): RedirectResponse
    {
        $this->authorize('delete', $university);

        $this->universities->delete($university);

        return redirect()
            ->route('universities.index')
            ->with('success', 'University deleted.');
    }
}
