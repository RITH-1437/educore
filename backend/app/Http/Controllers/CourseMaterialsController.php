<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseMaterialRequest;
use App\Http\Requests\UpdateCourseMaterialRequest;
use App\Http\Resources\CourseMaterialResource;
use App\Models\CourseMaterial;
use App\Models\Section;
use App\Services\CourseMaterialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Course materials on the web (`docs/44_Course-Materials-Report.md`): shared
 * from the section's coursework page, read by students on `/my-materials`.
 */
class CourseMaterialsController extends Controller
{
    public function __construct(private readonly CourseMaterialService $materials) {}

    public function mine(Request $request): Response
    {
        $student = $request->user()->student;
        abort_if($student === null, 403, 'No student profile is linked to this account.');

        return Inertia::render('Materials/Mine', [
            'materials' => CourseMaterialResource::collection($this->materials->forStudent($student))->resolve(),
        ]);
    }

    public function store(StoreCourseMaterialRequest $request, Section $section): RedirectResponse
    {
        $this->authorize('create', [CourseMaterial::class, $section]);

        $this->materials->create($section, $request->safe()->except('file'), $request->file('file'), $request->user());

        return back()->with('success', 'Material shared with the section.');
    }

    public function update(UpdateCourseMaterialRequest $request, CourseMaterial $material): RedirectResponse
    {
        $this->authorize('update', $material);

        $this->materials->update($material, $request->validated(), $request->user());

        return back()->with('success', 'Material updated.');
    }

    public function destroy(Request $request, CourseMaterial $material): RedirectResponse
    {
        $this->authorize('delete', $material);

        $this->materials->delete($material, $request->user());

        return back()->with('success', 'Material removed.');
    }

    public function download(CourseMaterial $material): StreamedResponse
    {
        $this->authorize('view', $material);

        return $this->materials->download($material);
    }
}
