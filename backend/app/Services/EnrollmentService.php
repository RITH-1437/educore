<?php

namespace App\Services;

use App\Enums\SemesterStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Course registration (module 9.9) — every path (student self-service and
 * admin) goes through `enroll()`, so no rule can be bypassed
 * (`skills/enrollment/SKILL.md` §4, §12).
 *
 * Checks, in order: student active → registration open (semester, period,
 * offering, section) → not already enrolled in the offering → strict
 * prerequisites passed → credit limit → seats (section capacity and offering
 * maximum, under a row lock so the last seat cannot be taken twice).
 */
class EnrollmentService
{
    /**
     * @param  array{student_id?: ?int, section_id?: ?int, semester_id?: ?int, status?: ?string, search?: ?string, per_page?: int}  $filters
     * @return LengthAwarePaginator<int, Enrollment>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return Enrollment::query()
            ->with(['student:id,student_number,first_name,last_name', 'section.offering.course:id,code,name,credits', 'semester:id,name,academic_year_id', 'semester.academicYear:id,code'])
            ->when($filters['student_id'] ?? null, fn ($q, $id) => $q->where('student_id', $id))
            ->when($filters['section_id'] ?? null, fn ($q, $id) => $q->where('section_id', $id))
            ->when($filters['semester_id'] ?? null, fn ($q, $id) => $q->where('semester_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->whereHas('student', fn ($s) => $s
                ->where('student_number', 'ilike', "%{$search}%")
                ->orWhere('first_name', 'ilike', "%{$search}%")
                ->orWhere('last_name', 'ilike', "%{$search}%")))
            ->orderByDesc('enrolled_at')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function enroll(Student $student, Section $section): Enrollment
    {
        return DB::transaction(function () use ($student, $section) {
            // Serialize concurrent registrations for this section.
            $section = Section::query()->whereKey($section->getKey())->lockForUpdate()->firstOrFail();
            $section->load('offering.course.prerequisites', 'offering.semester');
            $offering = $section->offering;
            $semester = $offering->semester;
            $course = $offering->course;

            if ($student->status !== Student::STATUS_ACTIVE) {
                throw new BusinessRuleException("A {$student->status} student cannot enroll.");
            }

            $this->assertRegistrationOpen($section);

            $existing = Enrollment::query()
                ->where('student_id', $student->getKey())
                ->whereIn('section_id', $offering->sections()->pluck('id'))
                ->get();

            if ($existing->contains(fn (Enrollment $e) => $e->isOpen() || $e->status === Enrollment::STATUS_COMPLETED)) {
                throw ValidationException::withMessages(['section_id' => "The student is already enrolled in {$course->code} this semester."]);
            }

            if ($existing->contains(fn (Enrollment $e) => $e->section_id === $section->getKey() && $e->status === Enrollment::STATUS_WITHDRAWN)) {
                throw ValidationException::withMessages(['section_id' => 'The student withdrew from this section and cannot re-enroll in it.']);
            }

            $missing = $this->missingPrerequisites($student, $course);
            if ($missing !== []) {
                throw ValidationException::withMessages(['section_id' => 'Prerequisites not met: '.implode(', ', $missing).'.']);
            }

            $this->assertCreditLimit($student, $semester->getKey(), (float) $course->credits);
            $this->assertSeats($section);

            $dropped = $existing->first(fn (Enrollment $e) => $e->section_id === $section->getKey());

            // `uq_enrollments_student_section`: re-enrolling after a drop
            // revives the original row instead of inserting a second one.
            if ($dropped !== null) {
                $dropped->update([
                    'status' => Enrollment::STATUS_CONFIRMED,
                    'enrolled_at' => now(),
                    'dropped_at' => null,
                ]);

                return $dropped->refresh();
            }

            return Enrollment::query()->create([
                'student_id' => $student->getKey(),
                'section_id' => $section->getKey(),
                'academic_year_id' => $semester->academic_year_id,
                'semester_id' => $semester->getKey(),
                'status' => Enrollment::STATUS_CONFIRMED,
                'enrolled_at' => now(),
            ])->refresh();
        });
    }

    /**
     * Drop an enrollment, keeping the record. Once attendance or a grade
     * exists the drop is recorded as a withdrawal.
     */
    public function drop(Enrollment $enrollment): Enrollment
    {
        return DB::transaction(function () use ($enrollment) {
            if (! $enrollment->isOpen()) {
                throw new BusinessRuleException("A {$enrollment->status} enrollment cannot be dropped.");
            }

            $hasRecords = DB::table('attendance_records')->where('enrollment_id', $enrollment->getKey())->exists()
                || DB::table('grades')->where('enrollment_id', $enrollment->getKey())->exists();

            $enrollment->update([
                'status' => $hasRecords ? Enrollment::STATUS_WITHDRAWN : Enrollment::STATUS_DROPPED,
                'dropped_at' => now(),
            ]);

            return $enrollment->refresh();
        });
    }

    /**
     * Mark a confirmed enrollment completed (until the grading module records
     * results, this is how a course counts as passed for prerequisites).
     */
    public function complete(Enrollment $enrollment): Enrollment
    {
        return DB::transaction(function () use ($enrollment) {
            if ($enrollment->status !== Enrollment::STATUS_CONFIRMED) {
                throw new BusinessRuleException('Only a confirmed enrollment can be completed.');
            }

            $enrollment->update(['status' => Enrollment::STATUS_COMPLETED]);

            return $enrollment->refresh();
        });
    }

    /**
     * Codes of strict prerequisites the student has not passed.
     *
     * Passed = a completed enrollment in any offering of that course whose
     * grade (if one exists) is not a fail (F / 0 points).
     *
     * @return list<string>
     */
    public function missingPrerequisites(Student $student, Course $course): array
    {
        $missing = [];

        foreach ($course->prerequisites as $prerequisite) {
            if (! $prerequisite->pivot->is_strict) {
                continue;
            }

            $passed = DB::table('enrollments')
                ->join('sections', 'sections.id', '=', 'enrollments.section_id')
                ->join('course_offerings', 'course_offerings.id', '=', 'sections.course_offering_id')
                ->leftJoin('grades', fn ($join) => $join->on('grades.enrollment_id', '=', 'enrollments.id')->whereNull('grades.deleted_at'))
                ->where('enrollments.student_id', $student->getKey())
                ->where('course_offerings.course_id', $prerequisite->getKey())
                ->where('enrollments.status', Enrollment::STATUS_COMPLETED)
                ->whereNull('enrollments.deleted_at')
                ->where(fn ($q) => $q->whereNull('grades.id')
                    ->orWhere(fn ($g) => $g
                        ->where(fn ($l) => $l->whereNull('grades.letter_grade')->orWhere('grades.letter_grade', '!=', 'F'))
                        ->where(fn ($p) => $p->whereNull('grades.grade_point')->orWhere('grades.grade_point', '>', 0))))
                ->exists();

            if (! $passed) {
                $missing[] = $prerequisite->code;
            }
        }

        return $missing;
    }

    public function openSeats(Section $section): int
    {
        return max(0, $section->capacity - $this->countOpen('section_id', $section->getKey()));
    }

    public function isRegistrationOpen(Section $section): bool
    {
        try {
            $this->assertRegistrationOpen($section);

            return true;
        } catch (BusinessRuleException) {
            return false;
        }
    }

    private function assertRegistrationOpen(Section $section): void
    {
        $semester = $section->offering->semester;
        $today = now()->startOfDay();

        if ($semester->status !== SemesterStatus::Open) {
            throw new BusinessRuleException('Registration is closed: the semester is not open.');
        }

        if (($semester->enrollment_start && $today->lt($semester->enrollment_start)) || ($semester->enrollment_end && $today->gt($semester->enrollment_end))) {
            throw new BusinessRuleException('Registration is closed: outside the enrollment period.');
        }

        if ($section->offering->status !== 'open') {
            throw new BusinessRuleException('Registration is closed for this course offering.');
        }

        if (! in_array($section->status, ['open', 'active'], true)) {
            throw new BusinessRuleException('This section is not open for registration.');
        }
    }

    private function assertCreditLimit(Student $student, int $semesterId, float $credits): void
    {
        $max = (float) config('academics.max_semester_credits', 24);

        $current = (float) DB::table('enrollments')
            ->join('sections', 'sections.id', '=', 'enrollments.section_id')
            ->join('course_offerings', 'course_offerings.id', '=', 'sections.course_offering_id')
            ->join('courses', 'courses.id', '=', 'course_offerings.course_id')
            ->where('enrollments.student_id', $student->getKey())
            ->where('enrollments.semester_id', $semesterId)
            ->whereIn('enrollments.status', Enrollment::OPEN_STATUSES)
            ->whereNull('enrollments.deleted_at')
            ->sum('courses.credits');

        if ($current + $credits > $max) {
            throw ValidationException::withMessages([
                'section_id' => "Credit limit exceeded: {$current} + {$credits} would be over the {$max}-credit semester limit.",
            ]);
        }
    }

    private function assertSeats(Section $section): void
    {
        if ($this->openSeats($section) <= 0) {
            throw new BusinessRuleException('This section is full.');
        }

        $max = $section->offering->max_enrollments;

        if ($max !== null) {
            $taken = DB::table('enrollments')
                ->join('sections', 'sections.id', '=', 'enrollments.section_id')
                ->where('sections.course_offering_id', $section->course_offering_id)
                ->whereIn('enrollments.status', Enrollment::OPEN_STATUSES)
                ->whereNull('enrollments.deleted_at')
                ->count();

            if ($taken >= $max) {
                throw new BusinessRuleException('This course offering is full.');
            }
        }
    }

    private function countOpen(string $column, int $id): int
    {
        return DB::table('enrollments')
            ->where($column, $id)
            ->whereIn('status', Enrollment::OPEN_STATUSES)
            ->whereNull('deleted_at')
            ->count();
    }
}
