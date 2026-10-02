<?php

namespace App\Services;

use App\Models\Course;
use App\Models\GpaRecord;
use App\Models\Grade;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * GPA (module 9.14, `skills/grading-gpa/SKILL.md` §4).
 *
 * - Credit-weighted: Σ(grade point × credits) / Σ(credits), approved grades only.
 * - Semester GPA counts every approved attempt in the semester.
 * - Cumulative GPA counts each course once — the latest approved attempt
 *   (retake replaces the earlier grade); F counts until replaced.
 * - Earned credits = credits of passing grades (grade point > 0).
 * - `gpa_records` are snapshots rebuilt from scratch on every change, so they
 *   can never go stale; zero attempted credits produce no row (no ÷ 0).
 */
class GpaService
{
    /** Rebuild every GPA snapshot of a student. Call inside the caller's transaction. */
    public function recalculate(Student $student): void
    {
        $rows = $this->approvedAttempts($student);

        GpaRecord::query()->where('student_id', $student->getKey())->delete();

        $now = now();

        foreach ($rows->groupBy('semester_id') as $attempts) {
            $first = $attempts->first();
            $this->store($student, $first->academic_year_id, $first->semester_id, false, $attempts, $now);
        }

        // Cumulative "as of the end of each year": latest attempt per course.
        foreach ($rows->pluck('academic_year_id')->unique() as $yearId) {
            $yearEnd = $rows->where('academic_year_id', $yearId)->max('order_key');
            $latest = $rows->filter(fn ($row) => $row->order_key <= $yearEnd)
                ->groupBy('course_id')
                ->map(fn (Collection $attempts) => $attempts->sortBy('order_key')->last());

            $this->store($student, $yearId, null, true, $latest, $now);
        }
    }

    /** Students whose GPA depends on a course (after its credits change). */
    public function recalculateForCourse(Course $course): void
    {
        Student::query()
            ->whereIn('id', Grade::query()
                ->join('enrollments', 'enrollments.id', '=', 'grades.enrollment_id')
                ->join('sections', 'sections.id', '=', 'enrollments.section_id')
                ->join('course_offerings', 'course_offerings.id', '=', 'sections.course_offering_id')
                ->where('course_offerings.course_id', $course->getKey())
                ->whereIn('grades.status', Grade::FINAL_STATUSES)
                ->select('enrollments.student_id'))
            ->get()
            ->each(fn (Student $student) => $this->recalculate($student));
    }

    /**
     * Semester GPAs (oldest first) and the latest cumulative GPA.
     *
     * @return array{semesters: list<array<string, mixed>>, cumulative: array<string, mixed>|null}
     */
    public function summary(Student $student): array
    {
        $records = GpaRecord::query()
            ->where('gpa_records.student_id', $student->getKey())
            ->join('academic_years', 'academic_years.id', '=', 'gpa_records.academic_year_id')
            ->leftJoin('semesters', 'semesters.id', '=', 'gpa_records.semester_id')
            ->orderBy('academic_years.start_date')
            ->orderBy('semesters.sequence')
            ->get(['gpa_records.*', 'academic_years.code as year_code', 'semesters.name as semester_name']);

        $shape = fn (GpaRecord $record) => [
            'academic_year_id' => $record->academic_year_id,
            'academic_year' => $record->year_code,
            'semester_id' => $record->semester_id,
            'semester' => $record->semester_name,
            'gpa' => (float) $record->gpa_value,
            'attempted_credits' => (float) $record->attempted_credits,
            'earned_credits' => (float) $record->earned_credits,
            'grade_points' => (float) $record->grade_points,
        ];

        $cumulative = $records->where('cumulative', true)->last();

        return [
            'semesters' => $records->where('cumulative', false)->map($shape)->values()->all(),
            'cumulative' => $cumulative ? $shape($cumulative) : null,
        ];
    }

    /**
     * Approved grades with credits and a chronological key.
     *
     * @return Collection<int, object>
     */
    private function approvedAttempts(Student $student): Collection
    {
        return DB::table('grades')
            ->join('enrollments', 'enrollments.id', '=', 'grades.enrollment_id')
            ->join('semesters', 'semesters.id', '=', 'enrollments.semester_id')
            ->join('academic_years', 'academic_years.id', '=', 'semesters.academic_year_id')
            ->join('sections', 'sections.id', '=', 'enrollments.section_id')
            ->join('course_offerings', 'course_offerings.id', '=', 'sections.course_offering_id')
            ->join('courses', 'courses.id', '=', 'course_offerings.course_id')
            ->where('enrollments.student_id', $student->getKey())
            ->whereIn('grades.status', Grade::FINAL_STATUSES)
            ->whereNotNull('grades.grade_point')
            ->whereNull('grades.deleted_at')
            ->whereNull('enrollments.deleted_at')
            ->orderBy('academic_years.start_date')
            ->orderBy('semesters.sequence')
            ->get([
                'grades.grade_point', 'courses.id as course_id', 'courses.credits',
                'semesters.id as semester_id', 'semesters.academic_year_id',
                'academic_years.start_date as year_start', 'semesters.sequence',
            ])
            ->map(function (object $row) {
                $row->order_key = $row->year_start.'#'.str_pad((string) $row->sequence, 3, '0', STR_PAD_LEFT);

                return $row;
            });
    }

    /**
     * @param  Collection<array-key, object>  $attempts
     */
    private function store(Student $student, int $yearId, ?int $semesterId, bool $cumulative, Collection $attempts, mixed $now): void
    {
        $attempted = $attempts->sum(fn ($row) => (float) $row->credits);

        if ($attempted <= 0) {
            return;
        }

        $points = $attempts->sum(fn ($row) => (float) $row->grade_point * (float) $row->credits);

        GpaRecord::query()->create([
            'student_id' => $student->getKey(),
            'academic_year_id' => $yearId,
            'semester_id' => $semesterId,
            'gpa_value' => round($points / $attempted, 3),
            'attempted_credits' => round($attempted, 2),
            'earned_credits' => round($attempts->filter(fn ($row) => (float) $row->grade_point > 0)->sum(fn ($row) => (float) $row->credits), 2),
            'grade_points' => round($points, 2),
            'cumulative' => $cumulative,
            'computed_at' => $now,
        ]);
    }
}
