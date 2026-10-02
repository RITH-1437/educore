<?php

namespace App\Services;

use App\Enums\SemesterStatus;
use App\Models\DocumentRequest;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Internship;
use App\Models\Invoice;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Institutional analytics (module 9.23, `skills/analytics-reporting`).
 *
 * - Read-only aggregates over the domain tables, computed in SQL with grouped
 *   queries — no fact tables, no PHP loops over rows.
 * - Academic and enrollment figures are bounded to one semester (default:
 *   the open semester with the latest start, else the latest semester).
 *   Administrative figures are the current workload (point in time).
 * - Reuses other modules' definitions instead of re-deriving them: approved /
 *   finalized grades (`Grade::FINAL_STATUSES`), the active grading scale, the
 *   attendance rate formula of 9.11, semester GPA snapshots of 9.14.
 * - Every ratio guards against division by zero (null = nothing to measure).
 */
class AnalyticsService
{
    public function __construct(
        private readonly GradingService $grading,
    ) {}

    public function defaultSemester(): ?Semester
    {
        return Semester::query()->where('status', SemesterStatus::Open->value)->orderByDesc('start_date')->first()
            ?? Semester::query()->orderByDesc('start_date')->orderByDesc('id')->first();
    }

    /**
     * @return list<array{id: int, name: string, status: string}>
     */
    public function semesterOptions(): array
    {
        return Semester::query()->with('academicYear:id,code')->orderByDesc('start_date')->orderByDesc('id')->get()
            ->map(fn (Semester $s) => ['id' => $s->id, 'name' => trim(($s->academicYear?->code ?? '').' '.$s->name), 'status' => $s->status->value])
            ->values()->all();
    }

    /**
     * Headline numbers for a semester plus current workload.
     *
     * @return array<string, mixed>
     */
    public function overview(Semester $semester): array
    {
        $attendance = $this->attendanceCounts($semester)->first();
        $grades = $this->finalGrades($semester)
            ->selectRaw('count(*) as graded, count(*) filter (where grades.grade_point > 0) as passed')
            ->first();

        return [
            'students_active' => Student::query()->where('status', Student::STATUS_ACTIVE)->count(),
            'lecturers_active' => DB::table('lecturers')->where('is_active', true)->count(),
            'sections' => DB::table('sections')->join('course_offerings', 'course_offerings.id', '=', 'sections.course_offering_id')
                ->where('course_offerings.semester_id', $semester->id)->whereIn('sections.status', ['open', 'active', 'closed', 'completed'])->count(),
            'enrollments' => $this->enrollments($semester)->count(),
            'students_enrolled' => (int) $this->enrollments($semester)->distinct()->count('enrollments.student_id'),
            'attendance_rate' => $this->rate((int) ($attendance->attended ?? 0), (int) ($attendance->counted ?? 0)),
            'grades_approved' => (int) ($grades->graded ?? 0),
            'pass_rate' => $this->rate((int) ($grades->passed ?? 0), (int) ($grades->graded ?? 0)),
            'average_gpa' => ($avg = DB::table('gpa_records')->where('semester_id', $semester->id)->where('cumulative', false)->avg('gpa_value')) === null ? null : round((float) $avg, 2),
        ];
    }

    /**
     * Enrollment in the semester per program (current program of the student)
     * and per enrollment status.
     *
     * @return array{by_program: list<array<string, mixed>>, by_status: list<array<string, mixed>>}
     */
    public function enrollment(Semester $semester): array
    {
        $byProgram = DB::table('enrollments')
            ->join('student_programs', fn ($j) => $j->on('student_programs.student_id', '=', 'enrollments.student_id')->where('student_programs.status', 'active'))
            ->join('programs', 'programs.id', '=', 'student_programs.program_id')
            ->where('enrollments.semester_id', $semester->id)
            ->whereIn('enrollments.status', [...Enrollment::OPEN_STATUSES, Enrollment::STATUS_COMPLETED])
            ->whereNull('enrollments.deleted_at')
            ->groupBy('programs.id', 'programs.code', 'programs.name')
            ->orderByDesc(DB::raw('count(*)'))
            ->get(['programs.code', 'programs.name', DB::raw('count(*) as enrollments'), DB::raw('count(distinct enrollments.student_id) as students')]);

        $byStatus = DB::table('enrollments')
            ->where('semester_id', $semester->id)->whereNull('deleted_at')
            ->groupBy('status')->orderBy('status')
            ->get(['status', DB::raw('count(*) as total')]);

        return [
            'by_program' => $byProgram->map(fn ($r) => ['code' => $r->code, 'name' => $r->name, 'enrollments' => (int) $r->enrollments, 'students' => (int) $r->students])->all(),
            'by_status' => $byStatus->map(fn ($r) => ['status' => $r->status, 'total' => (int) $r->total])->all(),
        ];
    }

    /**
     * Academic performance in the semester: letter distribution on the active
     * scale, semester-GPA distribution, attendance and results per course.
     *
     * @return array<string, mixed>
     */
    public function academic(Semester $semester): array
    {
        $letters = $this->finalGrades($semester)->groupBy('grades.letter_grade')
            ->selectRaw('grades.letter_grade, count(*) as total')
            ->pluck('total', 'letter_grade');
        $scale = $this->grading->activeScale();

        $gpaBands = DB::table('gpa_records')->where('semester_id', $semester->id)->where('cumulative', false)
            ->selectRaw('count(*) filter (where gpa_value < 1) as b0')
            ->selectRaw('count(*) filter (where gpa_value >= 1 and gpa_value < 2) as b1')
            ->selectRaw('count(*) filter (where gpa_value >= 2 and gpa_value < 3) as b2')
            ->selectRaw('count(*) filter (where gpa_value >= 3) as b3')
            ->first();

        $attendance = $this->attendanceCounts($semester)
            ->join('sections as s', 's.id', '=', 'attendance_sessions.section_id')
            ->join('course_offerings as o', 'o.id', '=', 's.course_offering_id')
            ->join('courses as c', 'c.id', '=', 'o.course_id')
            ->groupBy('c.id', 'c.code', 'c.name')
            ->addSelect('c.code', 'c.name')
            ->get()
            ->map(fn ($r) => ['code' => $r->code, 'name' => $r->name, 'rate' => $this->rate((int) $r->attended, (int) $r->counted)])
            ->filter(fn ($r) => $r['rate'] !== null)
            ->sortBy('rate')->values();

        $courses = $this->finalGrades($semester)
            ->join('courses as c', 'c.id', '=', 'o.course_id')
            ->groupBy('c.id', 'c.code', 'c.name')
            ->orderBy('c.code')
            ->get(['c.code', 'c.name', DB::raw('count(*) as graded'), DB::raw('count(*) filter (where grades.grade_point > 0) as passed'), DB::raw('avg(grades.total_score) as average'), DB::raw('avg(grades.grade_point) as average_point')])
            ->map(fn ($r) => [
                'code' => $r->code,
                'name' => $r->name,
                'graded' => (int) $r->graded,
                'pass_rate' => $this->rate((int) $r->passed, (int) $r->graded),
                'average_total' => $r->average === null ? null : round((float) $r->average, 1),
                'average_point' => $r->average_point === null ? null : round((float) $r->average_point, 2),
            ]);

        return [
            'grade_distribution' => $scale->map(fn ($band) => ['grade' => $band->grade, 'total' => (int) ($letters[$band->grade] ?? 0), 'is_pass' => $band->is_pass])->values()->all(),
            'gpa_distribution' => [
                ['band' => '0.00–0.99', 'total' => (int) ($gpaBands->b0 ?? 0)],
                ['band' => '1.00–1.99', 'total' => (int) ($gpaBands->b1 ?? 0)],
                ['band' => '2.00–2.99', 'total' => (int) ($gpaBands->b2 ?? 0)],
                ['band' => '3.00–4.00', 'total' => (int) ($gpaBands->b3 ?? 0)],
            ],
            'attendance_by_course' => $attendance->all(),
            'courses' => $courses->all(),
        ];
    }

    /**
     * Current administrative workload (point in time, not semester-bound).
     *
     * @return array<string, mixed>
     */
    public function administrative(): array
    {
        app(InvoiceService::class)->refreshOverdue();

        $count = fn ($query) => $query->groupBy('status')->selectRaw('status, count(*) as total')->pluck('total', 'status')->map(fn ($n) => (int) $n);

        $finance = DB::table('invoices')->whereNull('deleted_at')->where('status', '!=', Invoice::STATUS_CANCELLED)
            ->groupBy('currency')->orderByDesc('currency')
            ->get(['currency', DB::raw('sum(total) as invoiced'), DB::raw('sum(amount_paid) as collected'),
                DB::raw("coalesce(sum(total - amount_paid) filter (where status = 'overdue'), 0) as overdue"),
                DB::raw("count(*) filter (where status = 'overdue') as overdue_count")])
            ->map(fn ($r) => [
                'currency' => $r->currency,
                'invoiced' => round((float) $r->invoiced, 2),
                'collected' => round((float) $r->collected, 2),
                'outstanding' => round((float) $r->invoiced - (float) $r->collected, 2),
                'overdue' => round((float) $r->overdue, 2),
                'overdue_count' => (int) $r->overdue_count,
                'collection_rate' => $this->rate((int) round((float) $r->collected * 100), (int) round((float) $r->invoiced * 100)),
            ]);

        return [
            'documents' => $this->statusRows($count(DB::table('document_requests')), DocumentRequest::STATUSES),
            'internships' => $this->statusRows($count(DB::table('internships')), Internship::STATUSES),
            'invoices' => $this->statusRows($count(DB::table('invoices')->whereNull('deleted_at')), Invoice::STATUSES),
            'finance' => $finance->all(),
        ];
    }

    // ---------------------------------------------------------------- helpers

    private function enrollments(Semester $semester): Builder
    {
        return DB::table('enrollments')->where('enrollments.semester_id', $semester->id)
            ->whereIn('enrollments.status', [...Enrollment::OPEN_STATUSES, Enrollment::STATUS_COMPLETED])
            ->whereNull('enrollments.deleted_at');
    }

    /** Approved / finalized grades of the semester, joined to their offering as `o`. */
    private function finalGrades(Semester $semester): Builder
    {
        return DB::table('grades')
            ->join('enrollments as e', 'e.id', '=', 'grades.enrollment_id')
            ->join('sections as gs', 'gs.id', '=', 'e.section_id')
            ->join('course_offerings as o', 'o.id', '=', 'gs.course_offering_id')
            ->where('e.semester_id', $semester->id)
            ->whereIn('grades.status', Grade::FINAL_STATUSES)
            ->whereNull('grades.deleted_at');
    }

    /** Attendance records of held sessions in the semester (9.11: excused not counted). */
    private function attendanceCounts(Semester $semester): Builder
    {
        return DB::table('attendance_records')
            ->join('attendance_sessions', 'attendance_sessions.id', '=', 'attendance_records.attendance_session_id')
            ->where('attendance_sessions.status', 'held')
            ->whereIn('attendance_sessions.section_id', DB::table('sections')->join('course_offerings', 'course_offerings.id', '=', 'sections.course_offering_id')->where('course_offerings.semester_id', $semester->id)->select('sections.id'))
            ->selectRaw("count(*) filter (where attendance_records.status in ('present', 'late')) as attended")
            ->selectRaw("count(*) filter (where attendance_records.status in ('present', 'late', 'absent')) as counted");
    }

    private function rate(int $part, int $whole): ?float
    {
        return $whole === 0 ? null : round($part / $whole * 100, 1);
    }

    /**
     * @param  iterable<string, int>  $counts
     * @param  list<string>  $statuses
     * @return list<array{status: string, total: int}>
     */
    private function statusRows(iterable $counts, array $statuses): array
    {
        $counts = collect($counts);

        return array_map(fn ($status) => ['status' => $status, 'total' => (int) ($counts[$status] ?? 0)], $statuses);
    }
}
