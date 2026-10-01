<?php

namespace App\Services;

use App\Enums\SemesterStatus;
use App\Exceptions\BusinessRuleException;
use App\Http\Resources\ExamResource;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Examinations and results (module 9.13).
 *
 * - Scheduled dates fall inside the semester; completed semesters are frozen.
 * - Exam weights of a section never add up to more than 100 % (the full
 *   course weighting is `grading-gpa`, 9.14).
 * - A timed exam may not overlap another exam of the same section, or of any
 *   section sharing an enrolled student.
 * - Results: one per exam + enrollment, 0 ≤ score ≤ max, only students of the
 *   section, not before the exam date. Students see them once released.
 */
class ExamService
{
    /** Enrollment statuses that may hold exam results. */
    public const RESULT_STATUSES = [Enrollment::STATUS_PENDING, Enrollment::STATUS_CONFIRMED, Enrollment::STATUS_COMPLETED];

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Section $section, array $data): Exam
    {
        return DB::transaction(function () use ($section, $data) {
            $section->loadMissing('offering.semester');
            $this->assertOpenSemester($section);
            $this->assertRules($section, $data, null);

            return $section->exams()->create([...$data, 'is_published' => false])->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Exam $exam, array $data): Exam
    {
        return DB::transaction(function () use ($exam, $data) {
            $section = $exam->section->loadMissing('offering.semester');
            $this->assertOpenSemester($section);
            $this->assertRules($section, [...$exam->only(['weight', 'max_score', 'scheduled_date', 'start_time', 'end_time']), ...$data], $exam);

            if (isset($data['max_score'])) {
                $highest = (float) $exam->results()->max('score');

                if ((float) $data['max_score'] < $highest) {
                    throw ValidationException::withMessages(['max_score' => "A score of {$highest} has already been recorded; the maximum cannot be lower."]);
                }
            }

            $exam->update($data);

            return $exam->refresh();
        });
    }

    public function publish(Exam $exam, bool $published = true): Exam
    {
        $exam->update(['is_published' => $published]);

        return $exam->refresh();
    }

    public function delete(Exam $exam): void
    {
        DB::transaction(function () use ($exam) {
            if ($exam->results()->exists()) {
                throw new BusinessRuleException('This exam has results and cannot be deleted.');
            }

            $exam->delete();
        });
    }

    /**
     * Bulk upsert. Rows with neither a score nor remarks are ignored.
     *
     * @param  list<array{enrollment_id: int, score?: float|string|null, remarks?: string|null}>  $rows
     */
    public function record(Exam $exam, array $rows, User $by): int
    {
        return DB::transaction(function () use ($exam, $rows, $by) {
            $section = $exam->section->loadMissing('offering.semester');
            $this->assertOpenSemester($section);

            if ($exam->scheduled_date !== null && $exam->scheduled_date->isAfter(today())) {
                throw ValidationException::withMessages(['results' => 'Results cannot be recorded before the exam date ('.$exam->scheduled_date->toDateString().').']);
            }

            $allowed = Enrollment::query()->where('section_id', $exam->section_id)->whereIn('status', self::RESULT_STATUSES)->pluck('id')->all();
            $saved = 0;

            foreach ($rows as $i => $row) {
                $score = $row['score'] ?? null;
                $remarks = $row['remarks'] ?? null;

                if (($score === null || $score === '') && ($remarks === null || $remarks === '')) {
                    continue;
                }

                if (! in_array((int) $row['enrollment_id'], $allowed, true)) {
                    throw ValidationException::withMessages(["results.{$i}.enrollment_id" => 'This student is not enrolled in the section.']);
                }

                $this->assertScore($exam, $score, "results.{$i}.score");

                ExamResult::query()->updateOrCreate(
                    ['exam_id' => $exam->getKey(), 'enrollment_id' => (int) $row['enrollment_id']],
                    ['score' => $score === '' ? null : $score, 'remarks' => $remarks ?: null, 'recorded_by' => $by->getKey()],
                );
                $saved++;
            }

            return $saved;
        });
    }

    /** Correct one result. */
    public function correct(ExamResult $result, float|string|null $score, ?string $remarks, User $by): ExamResult
    {
        return DB::transaction(function () use ($result, $score, $remarks, $by) {
            $this->assertOpenSemester($result->exam->section->loadMissing('offering.semester'));
            $this->assertScore($result->exam, $score, 'score');

            $result->update(['score' => $score, 'remarks' => $remarks, 'recorded_by' => $by->getKey()]);

            return $result->refresh();
        });
    }

    /**
     * A section's roster with each student's result for the exam.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function roster(Exam $exam): Collection
    {
        $results = $exam->results()->get()->keyBy('enrollment_id');

        return Enrollment::query()
            ->where('section_id', $exam->section_id)
            ->whereIn('status', self::RESULT_STATUSES)
            ->with('student')
            ->get()
            ->sortBy(fn (Enrollment $enrollment) => $enrollment->student->fullName())
            ->values()
            ->map(fn (Enrollment $enrollment) => [
                'enrollment_id' => $enrollment->id,
                'student' => ['id' => $enrollment->student->id, 'student_number' => $enrollment->student->student_number, 'full_name' => $enrollment->student->fullName()],
                'result_id' => $results->get($enrollment->id)?->id,
                'score' => ($score = $results->get($enrollment->id)?->score) === null ? null : (float) $score,
                'remarks' => $results->get($enrollment->id)?->remarks,
            ]);
    }

    /**
     * A student's exams across their sections: schedule always, score only
     * once the exam's results are released.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function forStudent(Student $student): Collection
    {
        $enrollments = Enrollment::query()
            ->where('student_id', $student->getKey())
            ->whereIn('status', self::RESULT_STATUSES)
            ->with('section.offering.course:id,code,name', 'section.offering.semester:id,name,status')
            ->get()
            ->keyBy('section_id');

        return Exam::query()
            ->whereIn('section_id', $enrollments->keys())
            ->with(['results' => fn ($q) => $q->whereIn('enrollment_id', $enrollments->pluck('id'))])
            ->orderByRaw('scheduled_date IS NULL, scheduled_date, start_time')
            ->get()
            ->map(function (Exam $exam) use ($enrollments) {
                $section = $enrollments->get($exam->section_id)->section;
                $result = $exam->is_published ? $exam->results->first() : null;

                return [
                    ...(new ExamResource($exam))->resolve(),
                    'course' => ['code' => $section->offering->course->code, 'name' => $section->offering->course->name],
                    'section_code' => $section->code,
                    'semester' => $section->offering->semester->name,
                    'my_result' => $result ? ['score' => $result->score === null ? null : (float) $result->score, 'remarks' => $result->remarks] : null,
                ];
            });
    }

    private function assertOpenSemester(Section $section): void
    {
        if ($section->offering->semester->status === SemesterStatus::Completed) {
            throw new BusinessRuleException('The semester is completed; exams can no longer change.');
        }
    }

    /**
     * @param  array<string, mixed>  $data  the exam's resulting attributes
     */
    private function assertRules(Section $section, array $data, ?Exam $exam): void
    {
        $others = (float) $section->exams()->when($exam, fn ($q) => $q->whereKeyNot($exam->getKey()))->sum('weight');

        if ($others + (float) ($data['weight'] ?? 0) > 100) {
            throw ValidationException::withMessages(['weight' => 'Exam weights in this section would exceed 100% (others total '.round($others, 2).'%).']);
        }

        if (empty($data['scheduled_date'])) {
            return;
        }

        $date = Carbon::parse($data['scheduled_date']);
        $semester = $section->offering->semester;

        if (($semester->start_date && $date->lt($semester->start_date)) || ($semester->end_date && $date->gt($semester->end_date))) {
            throw ValidationException::withMessages(['scheduled_date' => 'The exam date must be within the semester ('.$semester->start_date?->toDateString().' – '.$semester->end_date?->toDateString().').']);
        }

        if (empty($data['start_time']) || empty($data['end_time'])) {
            return;
        }

        $start = substr((string) $data['start_time'], 0, 5);
        $end = substr((string) $data['end_time'], 0, 5);

        // Sections that share at least one open-enrolled student with this one.
        $students = Enrollment::query()->select('student_id')->where('section_id', $section->getKey())->whereIn('status', Enrollment::OPEN_STATUSES);
        $sharing = Enrollment::query()->select('section_id')->whereIn('student_id', $students)->whereIn('status', Enrollment::OPEN_STATUSES);

        $clash = Exam::query()
            ->when($exam, fn ($q) => $q->whereKeyNot($exam->getKey()))
            ->whereDate('scheduled_date', $date->toDateString())
            ->whereNotNull('start_time')->whereNotNull('end_time')
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->where(fn ($q) => $q->where('section_id', $section->getKey())->orWhereIn('section_id', $sharing))
            ->with('section.offering.course:id,code')
            ->first();

        if ($clash !== null) {
            $who = $clash->section_id === $section->getKey() ? 'this section' : 'students also enrolled in '.$clash->section->offering->course->code.' '.$clash->section->code;

            throw new BusinessRuleException("Clashes with “{$clash->title}” (".substr($clash->start_time, 0, 5).'–'.substr($clash->end_time, 0, 5).") for {$who}.");
        }
    }

    private function assertScore(Exam $exam, float|string|null $score, string $field): void
    {
        if ($score !== null && $score !== '' && (float) $score > (float) $exam->max_score) {
            throw ValidationException::withMessages([$field => 'The score cannot exceed the maximum of '.(float) $exam->max_score.'.']);
        }
    }
}
