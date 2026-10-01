<?php

namespace Database\Seeders;

use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Section;
use App\Services\ExamService;
use Illuminate\Database\Seeder;

/**
 * Seeds per open section a released midterm (held mid-semester, with results)
 * and an upcoming final (no results yet).
 *
 * Seed data is historical, so it is written directly rather than through
 * `ExamService`. The final is placed in the last week of the semester at a
 * slot derived from the section id so sections sharing students do not clash.
 * Idempotent: exams by `(section_id, title)`, results by
 * `(exam_id, enrollment_id)`.
 */
class ExamSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Section::query()->with('offering.semester', 'offering.course')->whereIn('status', ['open', 'active'])->orderBy('id')->get() as $section) {
            $semester = $section->offering->semester;
            $code = $section->offering->course->code;

            if ($semester->start_date === null || $semester->end_date === null) {
                continue;
            }

            $midDate = $semester->start_date->copy()->addWeeks(8)->startOfWeek()->addDays($section->id % 5);
            $finalDate = $semester->end_date->copy()->subWeek()->startOfWeek()->addDays($section->id % 5);
            $hour = 8 + ($section->id % 4) * 2;
            $slot = ['start_time' => sprintf('%02d:00', $hour), 'end_time' => sprintf('%02d:00', $hour + 2)];

            $midterm = Exam::query()->updateOrCreate(
                ['section_id' => $section->id, 'title' => "{$code} Midterm"],
                ['exam_type' => 'midterm', 'weight' => 30, 'max_score' => 100, 'scheduled_date' => $midDate, ...$slot, 'location' => 'Main hall', 'is_published' => $midDate->isPast()],
            );
            Exam::query()->updateOrCreate(
                ['section_id' => $section->id, 'title' => "{$code} Final"],
                ['exam_type' => 'final', 'weight' => 40, 'max_score' => 100, 'scheduled_date' => $finalDate, ...$slot, 'location' => 'Main hall', 'is_published' => false],
            );

            if (! $midDate->isPast()) {
                continue;
            }

            $enrollments = Enrollment::query()->where('section_id', $section->id)->whereIn('status', ExamService::RESULT_STATUSES)->orderBy('id')->get();

            foreach ($enrollments as $i => $enrollment) {
                ExamResult::query()->firstOrCreate(
                    ['exam_id' => $midterm->id, 'enrollment_id' => $enrollment->id],
                    ['score' => 55 + (($enrollment->id * 7 + $i * 11) % 43), 'remarks' => null],
                );
            }
        }
    }
}
