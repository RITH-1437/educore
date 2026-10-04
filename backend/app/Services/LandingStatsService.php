<?php

namespace App\Services;

/**
 * Live figures for the public landing page (`docs/37_Landing-Page-Redesign-Report.md`).
 *
 * - Aggregates only: counts, rates and distributions. No names, emails or other
 *   personal records leave this service.
 * - Reuses the University Admin dashboard (report 36) and institutional
 *   analytics (module 9.23), so the landing page shows exactly the numbers the
 *   real dashboards show. Read-only: nothing here writes (analytics'
 *   administrative() refreshes overdue invoices first, so it is not used).
 * - An empty database yields zeros, nulls and empty lists — the page then shows
 *   empty states rather than invented numbers.
 */
class LandingStatsService
{
    /** Per-course results are published only from this many graded students, so no individual result can be inferred. */
    public const MIN_COURSE_GROUP = 5;

    public function __construct(
        private readonly AnalyticsService $analytics,
        private readonly UniversityDashboardService $university,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $dashboard = $this->university->build();
        $semester = $this->analytics->defaultSemester();
        $academic = $semester ? $this->analytics->academic($semester) : null;

        $courses = collect($academic['courses'] ?? [])
            ->filter(fn (array $course) => $course['graded'] >= self::MIN_COURSE_GROUP)
            ->sortByDesc('graded')->take(5)->values()->all();

        return [
            'semester' => $dashboard['semester'],
            'waiting' => $dashboard['waiting'],
            'overview' => $dashboard['overview'],
            'enrollment_by_program' => $semester ? $this->analytics->enrollment($semester)['by_program'] : [],
            'grade_distribution' => $academic['grade_distribution'] ?? [],
            'courses' => $courses,
            'internships' => $this->analytics->internshipStatuses(),
        ];
    }
}
