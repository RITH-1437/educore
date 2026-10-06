<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\AnalyticsController as Api;
use App\Models\Department;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Analytics page (module 9.23): every section in one Inertia response,
 * filtered by semester and — for managers — by department; a Department Admin
 * always sees their own department (report 47).
 */
class AnalyticsPageController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analytics,
        private readonly Api $api,
    ) {}

    public function __invoke(Request $request): Response
    {
        Gate::authorize('view-analytics');
        // A Department Admin cannot change the department; managers pick one or none.
        $locked = $request->user()->departmentScope() !== null;

        // A Department Admin with no department has no unit data: a notice, no figures.
        if ($request->user()->departmentScope() === 0) {
            return Inertia::render('Analytics/Index', [
                'semesters' => [], 'semesterId' => null, 'departments' => [],
                'scope' => ['department' => null, 'locked' => true, 'unassigned' => true],
                'overview' => null, 'enrollment' => null, 'academic' => null, 'administrative' => null, 'trends' => [],
            ]);
        }

        $semester = $this->api->semester($request);
        $department = $this->api->departmentScope($request);

        return Inertia::render('Analytics/Index', [
            'semesters' => $this->analytics->semesterOptions(),
            'semesterId' => $semester?->id,
            'departments' => $locked ? [] : Department::query()->orderBy('name')->get(['id', 'name', 'code'])
                ->map(fn (Department $d) => ['id' => $d->id, 'name' => $d->name, 'code' => $d->code])->values(),
            'scope' => [
                'department' => $this->api->departmentSummary($department),
                'locked' => $locked,
                'unassigned' => false,
            ],
            'overview' => $semester ? $this->analytics->overview($semester, $department) : null,
            'enrollment' => $semester ? $this->analytics->enrollment($semester, $department) : null,
            'academic' => $semester ? $this->analytics->academic($semester, $department) : null,
            'administrative' => $this->analytics->administrative($department),
            'trends' => $this->analytics->trends($department),
        ]);
    }
}
