<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Assignment;
use App\Models\AttendanceSession;
use App\Models\Course;
use App\Models\CourseGradingConfig;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\GradingScale;
use App\Models\Section;
use App\Models\User;
use App\Notifications\GradePublished;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Grades (module 9.14, `skills/grading-gpa/SKILL.md`).
 *
 * Course total = Σ(component % × weight) / Σ(weights of the components that
 * have something to measure). Components and their sources:
 *
 * | Component  | Source                                                         |
 * |------------|----------------------------------------------------------------|
 * | attendance | the student's attendance rate (`AttendanceService`)            |
 * | assignment | published assignments + quiz / other exams (Σ score / Σ max)   |
 * | midterm    | midterm exams (Σ score / Σ max)                                |
 * | final      | final exams                                                    |
 * | practical  | practical exams                                                |
 *
 * An item counts once it has at least one recorded score in the section; a
 * student without a score on a counted item gets 0 for it. A component with
 * no counted item (or a student with no countable attendance) is left out and
 * the remaining weights are scaled up to 100.
 *
 * Workflow: compute drafts → submit (lecturer) → approve (manager, counts
 * toward GPA and completes the enrollment) or return to draft.
 */
class GradingService
{
    /** Enrollment statuses that receive a course grade. */
    public const GRADED_STATUSES = [Enrollment::STATUS_CONFIRMED, Enrollment::STATUS_COMPLETED];

    public const COMPONENTS = ['attendance', 'assignment', 'midterm', 'final', 'practical'];

    /** Default bands (skill §4): grade, minimum %, grade point, pass. */
    public const DEFAULT_BANDS = [
        ['grade' => 'A', 'min_percentage' => 85, 'grade_point' => 4.0, 'is_pass' => true],
        ['grade' => 'B+', 'min_percentage' => 80, 'grade_point' => 3.5, 'is_pass' => true],
        ['grade' => 'B', 'min_percentage' => 70, 'grade_point' => 3.0, 'is_pass' => true],
        ['grade' => 'C+', 'min_percentage' => 65, 'grade_point' => 2.5, 'is_pass' => true],
        ['grade' => 'C', 'min_percentage' => 50, 'grade_point' => 2.0, 'is_pass' => true],
        ['grade' => 'D', 'min_percentage' => 45, 'grade_point' => 1.0, 'is_pass' => true],
        ['grade' => 'F', 'min_percentage' => 0, 'grade_point' => 0.0, 'is_pass' => false],
    ];

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly AttendanceService $attendance,
        private readonly GpaService $gpa,
    ) {}

    // ------------------------------------------------------------------ scale

    /**
     * The active scale, highest band first.
     *
     * @return Collection<int, GradingScale>
     */
    public function activeScale(): Collection
    {
        return GradingScale::query()->active()->orderByDesc('min_percentage')->get();
    }

    /** Name of the active scale (or the default name when none exists). */
    public function activeScaleName(): string
    {
        return GradingScale::query()->active()->value('name') ?? GradingScale::DEFAULT_NAME;
    }

    /**
     * Replace the bands of the active scale. `max_percentage` is derived from
     * the next band up, so the scale always covers 0–100 without gaps.
     *
     * @param  list<array{grade: string, min_percentage: float|int|string, grade_point: float|int|string, is_pass?: bool}>  $bands
     * @return Collection<int, GradingScale>
     */
    public function saveScale(array $bands): Collection
    {
        $sorted = collect($bands)->sortBy(fn ($band) => (float) $band['min_percentage'])->values();

        if ((float) $sorted->first()['min_percentage'] !== 0.0) {
            throw ValidationException::withMessages(['bands' => 'The lowest band must start at 0%.']);
        }

        foreach ($sorted as $i => $band) {
            $next = $sorted->get($i + 1);

            if ($next !== null && (float) $next['grade_point'] < (float) $band['grade_point']) {
                throw ValidationException::withMessages(['bands' => "Grade {$next['grade']} needs a higher percentage than {$band['grade']} but has fewer grade points."]);
            }
        }

        return DB::transaction(function () use ($sorted) {
            $before = $this->scaleSnapshot();
            $name = $this->activeScaleName();
            GradingScale::query()->where('name', $name)->delete();

            foreach ($sorted as $i => $band) {
                $next = $sorted->get($i + 1);

                GradingScale::query()->create([
                    'name' => $name,
                    'grade' => strtoupper(trim($band['grade'])),
                    'min_percentage' => $band['min_percentage'],
                    'max_percentage' => $next === null ? 100 : round((float) $next['min_percentage'] - 0.01, 2),
                    'grade_point' => $band['grade_point'],
                    'is_pass' => (bool) ($band['is_pass'] ?? ((float) $band['grade_point'] > 0)),
                    'is_active' => true,
                ]);
            }

            $this->audit->record('grading_scale.updated', null, ['bands' => $before], ['bands' => $this->scaleSnapshot()]);

            return $this->activeScale();
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function scaleSnapshot(): array
    {
        return $this->activeScale()->map(fn (GradingScale $band) => [
            'grade' => $band->grade,
            'min_percentage' => (float) $band->min_percentage,
            'grade_point' => (float) $band->grade_point,
            'is_pass' => $band->is_pass,
        ])->values()->all();
    }

    /**
     * Letter + point for a total (0–100) on the active scale.
     *
     * @param  Collection<int, GradingScale>|null  $scale
     * @return array{letter: string, point: float, is_pass: bool}|null
     */
    public function letterFor(?float $total, ?Collection $scale = null): ?array
    {
        if ($total === null) {
            return null;
        }

        $band = ($scale ?? $this->activeScale())->first(fn (GradingScale $band) => round($total, 2) >= (float) $band->min_percentage);

        return $band ? ['letter' => $band->grade, 'point' => (float) $band->grade_point, 'is_pass' => $band->is_pass] : null;
    }

    // ---------------------------------------------------------------- weights

    /** The course's weights, or an unsaved default (10/25/20/40/5). */
    public function configFor(Course $course): CourseGradingConfig
    {
        return $course->gradingConfig()->first() ?? new CourseGradingConfig(['course_id' => $course->getKey()]);
    }

    /**
     * @param  array<string, float|int|string>  $weights
     */
    public function saveConfig(Course $course, array $weights): CourseGradingConfig
    {
        $sum = array_sum(array_map(fn ($component) => (float) ($weights["{$component}_weight"] ?? 0), self::COMPONENTS));

        if (abs($sum - 100) > 0.001) {
            throw ValidationException::withMessages(['weights' => 'The weights must add up to 100% (currently '.round($sum, 2).'%).']);
        }

        return DB::transaction(function () use ($course, $weights) {
            $before = $this->weights($this->configFor($course));
            $config = CourseGradingConfig::query()->updateOrCreate(['course_id' => $course->getKey()], $weights)->refresh();
            $this->audit->record('grading_config.updated', $course, $before, $this->weights($config));

            return $config;
        });
    }

    /**
     * @return array<string, float>
     */
    public function weights(CourseGradingConfig $config): array
    {
        return collect(self::COMPONENTS)->mapWithKeys(fn ($component) => [$component => (float) $config->{"{$component}_weight"}])->all();
    }

    // ------------------------------------------------------------------ sheet

    /**
     * The section's grade sheet: per student, every component %, the live
     * total and letter, and the stored grade.
     *
     * @return array{weights: array<string, float>, components: array<string, bool>, rows: Collection<int, array<string, mixed>>}
     */
    public function sheet(Section $section): array
    {
        $section->loadMissing('offering.course');
        $weights = $this->weights($this->configFor($section->offering->course));
        $scale = $this->activeScale();

        $enrollments = $section->enrollments()
            ->whereIn('status', self::GRADED_STATUSES)
            ->with('student:id,student_number,first_name,last_name', 'grade')
            ->get();

        $components = $this->componentScores($section, $enrollments);
        $available = collect($components)->map(fn ($scores) => $scores !== null)->all();

        $rows = $enrollments->map(function (Enrollment $enrollment) use ($components, $weights, $scale) {
            $percentages = collect($components)->map(fn ($scores) => $scores === null ? null : ($scores[$enrollment->id] ?? null));
            $total = $this->total($percentages->all(), $weights);
            $computed = $this->letterFor($total, $scale);
            $grade = $enrollment->grade;

            return [
                'enrollment_id' => $enrollment->id,
                'student' => ['id' => $enrollment->student->id, 'student_number' => $enrollment->student->student_number, 'full_name' => $enrollment->student->fullName()],
                'components' => $percentages->map(fn ($value) => $value === null ? null : round($value, 2))->all(),
                'computed' => ['total' => $total, 'letter' => $computed['letter'] ?? null, 'grade_point' => $computed['point'] ?? null],
                'grade' => $grade ? [
                    'id' => $grade->id,
                    'status' => $grade->status,
                    'total_score' => $grade->total_score === null ? null : (float) $grade->total_score,
                    'letter_grade' => $grade->letter_grade,
                    'grade_point' => $grade->grade_point === null ? null : (float) $grade->grade_point,
                    'remarks' => $grade->remarks,
                    'submitted_at' => $grade->submitted_at?->toIso8601String(),
                    'approved_at' => $grade->approved_at?->toIso8601String(),
                ] : null,
            ];
        })->sortBy('student.full_name')->values();

        return ['weights' => $weights, 'components' => $available, 'rows' => $rows];
    }

    /**
     * Status counts of a section's grades.
     *
     * @return array{draft: int, submitted: int, approved: int, students: int}
     */
    public function statusCounts(Section $section): array
    {
        $counts = Grade::query()
            ->whereIn('enrollment_id', $section->enrollments()->whereIn('status', self::GRADED_STATUSES)->select('id'))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'draft' => (int) ($counts[Grade::STATUS_DRAFT] ?? 0),
            'submitted' => (int) ($counts[Grade::STATUS_SUBMITTED] ?? 0),
            'approved' => (int) ($counts[Grade::STATUS_APPROVED] ?? 0),
            'finalized' => (int) ($counts[Grade::STATUS_FINALIZED] ?? 0),
            'students' => $section->enrollments()->whereIn('status', self::GRADED_STATUSES)->count(),
        ];
    }

    // --------------------------------------------------------------- workflow

    /**
     * Save the computed totals as draft grades. Submitted / approved grades
     * are left untouched. Returns the number of drafts written.
     *
     * @param  array<int, string|null>  $remarks  keyed by enrollment id
     */
    public function compute(Section $section, User $by, array $remarks = []): int
    {
        return DB::transaction(function () use ($section, $by, $remarks) {
            $saved = 0;

            foreach ($this->sheet($section)['rows'] as $row) {
                if ($row['grade'] !== null && $row['grade']['status'] !== Grade::STATUS_DRAFT) {
                    continue;
                }

                $grade = Grade::withTrashed()->firstOrNew(['enrollment_id' => $row['enrollment_id']]);
                $grade->fill([
                    'total_score' => $row['computed']['total'],
                    'letter_grade' => $row['computed']['letter'],
                    'grade_point' => $row['computed']['grade_point'],
                    'status' => Grade::STATUS_DRAFT,
                    'graded_by' => $by->getKey(),
                    'remarks' => array_key_exists($row['enrollment_id'], $remarks) ? ($remarks[$row['enrollment_id']] ?: null) : $grade->remarks,
                ]);
                $grade->deleted_at = null;
                $grade->save();
                $saved++;
            }

            return $saved;
        });
    }

    /** Lecturer hands the drafts over for approval. */
    public function submit(Section $section, User $by): int
    {
        return DB::transaction(function () use ($section, $by) {
            $grades = $this->gradesOf($section)->lockForUpdate()->get();
            $students = $section->enrollments()->whereIn('status', self::GRADED_STATUSES)->count();
            $drafts = $grades->where('status', Grade::STATUS_DRAFT);

            if ($drafts->isEmpty()) {
                throw new BusinessRuleException('There are no draft grades to submit.');
            }

            if ($grades->count() < $students) {
                throw ValidationException::withMessages(['grades' => 'Compute grades for every student before submitting ('.($students - $grades->count()).' missing).']);
            }

            if (($blank = $drafts->whereNull('letter_grade')->count()) > 0) {
                throw ValidationException::withMessages(['grades' => "{$blank} student".($blank === 1 ? ' has' : 's have').' no recorded scores yet, so no grade can be submitted.']);
            }

            foreach ($drafts as $grade) {
                $grade->update(['status' => Grade::STATUS_SUBMITTED, 'submitted_at' => now(), 'graded_by' => $by->getKey()]);
            }

            $this->audit->record('grades.submitted', $section, after: ['grades' => $this->gradeSnapshot($drafts)]);

            return $drafts->count();
        });
    }

    /**
     * Manager approves the submitted grades: they count toward GPA and
     * prerequisites, and confirmed enrollments become completed.
     */
    public function approve(Section $section): int
    {
        return DB::transaction(function () use ($section) {
            $grades = $this->gradesOf($section)->where('status', Grade::STATUS_SUBMITTED)->with('enrollment.student')->lockForUpdate()->get();

            if ($grades->isEmpty()) {
                throw new BusinessRuleException('There are no submitted grades to approve.');
            }

            foreach ($grades as $grade) {
                $grade->update(['status' => Grade::STATUS_APPROVED, 'approved_at' => now()]);

                if ($grade->enrollment->status === Enrollment::STATUS_CONFIRMED) {
                    $grade->enrollment->update(['status' => Enrollment::STATUS_COMPLETED]);
                }

                $grade->enrollment->student->user?->notify(new GradePublished($grade));
            }

            $this->audit->record('grades.approved', $section, after: ['grades' => $this->gradeSnapshot($grades)]);

            $grades->pluck('enrollment.student')->unique('id')->each(fn ($student) => $this->gpa->recalculate($student));

            return $grades->count();
        });
    }

    /** Manager sends submitted / approved grades back to draft; GPA is recomputed. */
    public function returnToDraft(Section $section): int
    {
        return DB::transaction(function () use ($section) {
            $grades = $this->gradesOf($section)->whereIn('status', [Grade::STATUS_SUBMITTED, Grade::STATUS_APPROVED])->with('enrollment.student')->lockForUpdate()->get();

            if ($grades->isEmpty()) {
                throw new BusinessRuleException('There are no submitted or approved grades to return.');
            }

            $before = $grades->mapWithKeys(fn (Grade $grade) => [$grade->id => $grade->status])->all();

            foreach ($grades as $grade) {
                $grade->update(['status' => Grade::STATUS_DRAFT, 'submitted_at' => null, 'approved_at' => null]);
            }

            $this->audit->record('grades.returned', $section, ['statuses' => $before], ['grades' => $this->gradeSnapshot($grades)]);

            $grades->pluck('enrollment.student')->unique('id')->each(fn ($student) => $this->gpa->recalculate($student));

            return $grades->count();
        });
    }

    /**
     * Lock approved grades (`finalized`). Finalized grades cannot be returned
     * to draft; they still count toward GPA exactly like approved ones.
     */
    public function finalize(Section $section): int
    {
        return DB::transaction(function () use ($section) {
            $grades = $this->gradesOf($section)->where('status', Grade::STATUS_APPROVED)->lockForUpdate()->get();

            if ($grades->isEmpty()) {
                throw new BusinessRuleException('There are no approved grades to finalize.');
            }

            foreach ($grades as $grade) {
                $grade->update(['status' => Grade::STATUS_FINALIZED]);
            }

            $this->audit->record('grades.finalized', $section, after: ['grades' => $this->gradeSnapshot($grades)]);

            return $grades->count();
        });
    }

    /**
     * Unlock finalized grades back to approved so they can be corrected
     * (`skills/grading-gpa` §10: a change after finalization needs permission
     * and an audit trail — Super Admin only, with a reason). GPA is unchanged.
     */
    public function reopen(Section $section, string $reason): int
    {
        return DB::transaction(function () use ($section, $reason) {
            $grades = $this->gradesOf($section)->where('status', Grade::STATUS_FINALIZED)->lockForUpdate()->get();

            if ($grades->isEmpty()) {
                throw new BusinessRuleException('There are no finalized grades to reopen.');
            }

            foreach ($grades as $grade) {
                $grade->update(['status' => Grade::STATUS_APPROVED]);
            }

            $this->audit->record('grades.reopened', $section, ['status' => Grade::STATUS_FINALIZED], ['grades' => $this->gradeSnapshot($grades)], $reason);

            return $grades->count();
        });
    }

    // ---------------------------------------------------------------- student

    /**
     * A student's approved grades, oldest semester first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function forStudent(int $studentId): Collection
    {
        return Grade::query()
            ->whereIn('status', Grade::FINAL_STATUSES)
            ->whereHas('enrollment', fn ($q) => $q->where('student_id', $studentId))
            ->with('enrollment.section.offering.course:id,code,name,credits', 'enrollment.semester.academicYear:id,code,start_date')
            ->get()
            ->sortBy(fn (Grade $grade) => $grade->enrollment->semester->academicYear->start_date?->toDateString().'#'.str_pad((string) $grade->enrollment->semester->sequence, 3, '0', STR_PAD_LEFT).'#'.$grade->enrollment->section->offering->course->code)
            ->values()
            ->map(fn (Grade $grade) => [
                'id' => $grade->id,
                'course' => ['code' => $grade->enrollment->section->offering->course->code, 'name' => $grade->enrollment->section->offering->course->name],
                'credits' => (float) $grade->enrollment->section->offering->course->credits,
                'section' => $grade->enrollment->section->code,
                'semester_id' => $grade->enrollment->semester_id,
                'semester' => trim(($grade->enrollment->semester->academicYear?->code ?? '').' '.$grade->enrollment->semester->name),
                'total_score' => $grade->total_score === null ? null : (float) $grade->total_score,
                'letter_grade' => $grade->letter_grade,
                'grade_point' => $grade->grade_point === null ? null : (float) $grade->grade_point,
                'remarks' => $grade->remarks,
                'approved_at' => $grade->approved_at?->toIso8601String(),
            ]);
    }

    // ---------------------------------------------------------------- helpers

    /**
     * Who got what, for the audit trail.
     *
     * @param  iterable<Grade>  $grades
     * @return list<array<string, mixed>>
     */
    private function gradeSnapshot(iterable $grades): array
    {
        return collect($grades)->map(fn (Grade $grade) => [
            'grade_id' => $grade->id,
            'enrollment_id' => $grade->enrollment_id,
            'letter_grade' => $grade->letter_grade,
            'total_score' => $grade->total_score === null ? null : (float) $grade->total_score,
            'status' => $grade->status,
        ])->values()->all();
    }

    private function gradesOf(Section $section): Builder
    {
        return Grade::query()->whereIn('enrollment_id', $section->enrollments()->whereIn('status', self::GRADED_STATUSES)->select('id'));
    }

    /**
     * Total of the available components, weights rescaled to 100.
     *
     * @param  array<string, float|null>  $percentages
     * @param  array<string, float>  $weights
     */
    private function total(array $percentages, array $weights): ?float
    {
        $weightSum = 0.0;
        $sum = 0.0;

        foreach ($percentages as $component => $value) {
            if ($value === null || $weights[$component] <= 0) {
                continue;
            }

            $weightSum += $weights[$component];
            $sum += $value * $weights[$component];
        }

        return $weightSum > 0 ? round($sum / $weightSum, 2) : null;
    }

    /**
     * Per component: null when nothing is measurable in the section,
     * otherwise a map enrollment id → percentage (null = nothing for that student).
     *
     * @param  Collection<int, Enrollment>  $enrollments
     * @return array<string, array<int, float|null>|null>
     */
    private function componentScores(Section $section, Collection $enrollments): array
    {
        $ids = $enrollments->modelKeys();

        $attendance = $this->attendance->sectionSummary($section)->pluck('rate', 'enrollment_id');
        $hasSessions = AttendanceSession::query()->where('section_id', $section->getKey())->where('status', 'held')->exists();

        // Items with at least one recorded score: [kind, max, scores by enrollment].
        $exams = Exam::query()->where('section_id', $section->getKey())
            ->whereHas('results', fn ($q) => $q->whereNotNull('score'))
            ->with(['results' => fn ($q) => $q->whereIn('enrollment_id', $ids)])
            ->get();
        $assignments = Assignment::query()->where('section_id', $section->getKey())->where('is_published', true)
            ->whereHas('submissions', fn ($q) => $q->whereNotNull('score'))
            ->with(['submissions' => fn ($q) => $q->whereIn('enrollment_id', $ids)])
            ->get();

        $items = collect(self::COMPONENTS)->mapWithKeys(fn ($component) => [$component => collect()])->all();

        foreach ($exams as $exam) {
            $component = in_array($exam->exam_type, ['midterm', 'final', 'practical'], true) ? $exam->exam_type : 'assignment';
            $items[$component]->push([(float) $exam->max_score, $exam->results->pluck('score', 'enrollment_id')]);
        }

        foreach ($assignments as $assignment) {
            $items['assignment']->push([(float) $assignment->max_score, $assignment->submissions->pluck('score', 'enrollment_id')]);
        }

        $result = [];

        foreach (self::COMPONENTS as $component) {
            if ($component === 'attendance') {
                $result[$component] = $hasSessions ? collect($ids)->mapWithKeys(fn ($id) => [$id => $attendance->get($id)])->all() : null;

                continue;
            }

            if ($items[$component]->isEmpty()) {
                $result[$component] = null;

                continue;
            }

            $max = $items[$component]->sum(fn ($item) => $item[0]);
            $result[$component] = collect($ids)->mapWithKeys(fn ($id) => [
                $id => $max > 0 ? $items[$component]->sum(fn ($item) => (float) ($item[1]->get($id) ?? 0)) / $max * 100 : null,
            ])->all();
        }

        return $result;
    }
}
