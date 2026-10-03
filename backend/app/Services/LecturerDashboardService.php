<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Lecturer;
use App\Models\Section;
use App\Models\Semester;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Lecturer dashboard (`docs/35_Lecturer-Dashboard-Report.md`): the teaching
 * work of the current semester at a glance.
 *
 * Every figure comes from the owning module's service — classes from
 * `TimetableService`, missing registers from `AttendanceService`, submissions
 * to grade from `AssignmentService`, exams from `ExamService`, grade-sheet
 * progress from `GradingService` — so nothing is re-derived here. The current
 * semester is the analytics default, as on the Faculty Admin dashboard; only
 * the lecturer's sections in it are shown.
 */
class LecturerDashboardService
{
    private const LIMIT = 5;

    public function __construct(
        private readonly AnalyticsService $analytics,
        private readonly TimetableService $timetable,
        private readonly AttendanceService $attendance,
        private readonly AssignmentService $assignments,
        private readonly ExamService $exams,
        private readonly GradingService $grading,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Lecturer $lecturer): array
    {
        $semester = $this->analytics->defaultSemester();
        $sections = $semester === null ? collect() : $lecturer->sections()
            ->whereHas('offering', fn ($query) => $query->where('semester_id', $semester->id))
            ->with('offering.course:id,code,name')
            ->withCount(['enrollments' => fn ($query) => $query->whereIn('status', Enrollment::OPEN_STATUSES)])
            ->get();
        $ids = $sections->pluck('id')->all();
        $toGrade = $this->assignments->toGradeCounts($ids);

        $rows = $sections->map(fn (Section $section) => [
            'id' => $section->id,
            'code' => $section->code,
            'course' => ['code' => $section->offering->course->code, 'name' => $section->offering->course->name],
            'role' => $section->pivot->role,
            'students' => $section->enrollments_count,
            'registers_to_take' => $this->attendance->expectedDates($section)->where('recorded', false)->count(),
            'submissions_to_grade' => $toGrade[$section->id] ?? 0,
            'grades' => $this->gradeSheetState($this->grading->statusCounts($section)),
        ])->sortBy(fn (array $row) => $row['course']['code'].' '.$row['code'])->values();

        $today = $this->today($lecturer, $semester);
        $exams = $this->exams->upcoming($ids);

        return [
            'lecturer' => ['id' => $lecturer->id, 'staff_number' => $lecturer->staff_number, 'full_name' => $lecturer->fullName()],
            'semester' => $semester ? ['id' => $semester->id, 'name' => trim(($semester->academicYear?->code ?? '').' '.$semester->name)] : null,
            'counts' => [
                'classes_today' => $today->count(),
                'registers_to_take' => $rows->sum('registers_to_take'),
                'submissions_to_grade' => $rows->sum('submissions_to_grade'),
                'upcoming_exams' => $exams->count(),
            ],
            'today' => $today,
            'exams' => $exams->take(self::LIMIT)->values(),
            'sections' => $rows,
        ];
    }

    /**
     * Today's meetings, when today falls inside the semester.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function today(Lecturer $lecturer, ?Semester $semester): Collection
    {
        $today = Carbon::today();

        if ($semester === null || ! $semester->covers($today)) {
            return collect();
        }

        return $this->timetable->forLecturer($lecturer, $semester)
            ->where('day_of_week', $today->dayOfWeekIso)
            ->values();
    }

    /**
     * One state for a section's grade sheet, from `GradingService::statusCounts()`:
     * `not_started`, `draft` (some grades not submitted yet), `submitted`,
     * `approved`, `finalized`, or `no_students`.
     *
     * @param  array{draft: int, submitted: int, approved: int, finalized: int, students: int}  $counts
     */
    private function gradeSheetState(array $counts): string
    {
        $students = $counts['students'];
        $done = $counts['submitted'] + $counts['approved'] + $counts['finalized'];

        return match (true) {
            $students === 0 => 'no_students',
            $counts['finalized'] === $students => 'finalized',
            $students === $counts['approved'] + $counts['finalized'] => 'approved',
            $done === $students => 'submitted',
            $counts['draft'] > 0 || $done > 0 => 'draft',
            default => 'not_started',
        };
    }
}
