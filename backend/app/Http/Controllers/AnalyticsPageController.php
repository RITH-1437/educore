<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\AnalyticsController as Api;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Analytics page (module 9.23): every section in one Inertia response,
 * filtered by semester.
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
        $semester = $this->api->semester($request);

        return Inertia::render('Analytics/Index', [
            'semesters' => $this->analytics->semesterOptions(),
            'semesterId' => $semester?->id,
            'overview' => $semester ? $this->analytics->overview($semester) : null,
            'enrollment' => $semester ? $this->analytics->enrollment($semester) : null,
            'academic' => $semester ? $this->analytics->academic($semester) : null,
            'administrative' => $this->analytics->administrative(),
        ]);
    }
}
