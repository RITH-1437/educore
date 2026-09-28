<?php

namespace App\Services;

use App\Enums\AcademicYearStatus;
use App\Enums\SemesterStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\AcademicYear;
use App\Models\Semester;
use Illuminate\Support\Facades\DB;

/**
 * Semester business rules.
 *
 * A semester is always handled in the context of its academic year, and its
 * dates must stay inside that year's span.
 */
class SemesterService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(AcademicYear $academicYear, array $attributes): Semester
    {
        return DB::transaction(function () use ($academicYear, $attributes) {
            if ($academicYear->status === AcademicYearStatus::Completed) {
                throw new BusinessRuleException('A completed academic year cannot receive new semesters.');
            }

            if (($attributes['status'] ?? null) === SemesterStatus::Completed->value) {
                throw new BusinessRuleException('A new semester cannot be created as completed.');
            }

            $semester = $academicYear->semesters()->create($attributes);

            $this->assertDatesWithinYear($academicYear, $semester);

            return $semester->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Semester $semester, array $attributes): Semester
    {
        $status = isset($attributes['status'])
            ? SemesterStatus::from((string) $attributes['status'])
            : null;
        unset($attributes['status']);

        return DB::transaction(function () use ($semester, $attributes, $status) {
            // `status` never moves through a plain assignment, so a crafted
            // payload cannot skip the transition rules.
            if ($status !== null) {
                $this->applyStatus($semester, $status);
            }

            if ($attributes !== []) {
                $semester->update($attributes);
            }

            $this->assertDatesWithinYear($semester->academicYear, $semester);

            return $semester->refresh();
        });
    }

    /**
     * Move a semester along planned -> open -> closed -> completed.
     */
    public function changeStatus(Semester $semester, SemesterStatus $status): Semester
    {
        return DB::transaction(fn () => $this->applyStatus($semester, $status));
    }

    public function delete(Semester $semester): void
    {
        DB::transaction(function () use ($semester) {
            if (DB::table('course_offerings')->where('semester_id', $semester->getKey())->exists()) {
                throw new BusinessRuleException('This semester already has course offerings and cannot be deleted.');
            }

            $semester->delete();
        });
    }

    /**
     * The semester calendar must not spill outside its academic year.
     */
    private function assertDatesWithinYear(AcademicYear $academicYear, Semester $semester): void
    {
        $spans = [
            'start_date' => [$semester->start_date, $academicYear->start_date, $academicYear->end_date],
            'end_date' => [$semester->end_date, $academicYear->start_date, $academicYear->end_date],
            'enrollment_start' => [$semester->enrollment_start, $academicYear->start_date, $academicYear->end_date],
            'enrollment_end' => [$semester->enrollment_end, $academicYear->start_date, $academicYear->end_date],
            'exam_start' => [$semester->exam_start, $academicYear->start_date, $academicYear->end_date],
            'exam_end' => [$semester->exam_end, $academicYear->start_date, $academicYear->end_date],
        ];

        foreach ($spans as $field => [$value, $yearStart, $yearEnd]) {
            if ($value !== null && ($value->lt($yearStart) || $value->gt($yearEnd))) {
                throw new BusinessRuleException(
                    "The semester {$field} must fall inside the academic year {$academicYear->code}."
                );
            }
        }
    }

    /**
     * Apply a status transition, rejecting any move the domain forbids.
     */
    private function applyStatus(Semester $semester, SemesterStatus $status): Semester
    {
        $current = $semester->status;

        if ($current !== $status && ! $current->canTransitionTo($status)) {
            throw new BusinessRuleException(
                "A semester cannot move from {$current->label()} to {$status->label()}."
            );
        }

        $semester->update(['status' => $status]);

        return $semester;
    }
}
