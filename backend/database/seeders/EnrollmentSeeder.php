<?php

namespace Database\Seeders;

use App\Exceptions\BusinessRuleException;
use App\Models\Section;
use App\Models\Student;
use App\Services\EnrollmentService;
use Illuminate\Database\Seeder;
use Illuminate\Validation\ValidationException;

/**
 * Enrolls seeded students in open sections of their program's courses.
 *
 * Goes through `EnrollmentService::enroll()` on purpose, so seeded data obeys
 * every rule (open registration, prerequisites, credit limit, seats). Anything
 * the rules refuse — including "already enrolled" on re-runs, which makes the
 * seeder idempotent — is skipped.
 */
class EnrollmentSeeder extends Seeder
{
    public function run(EnrollmentService $enrollments): void
    {
        $sections = Section::query()
            ->with('offering.course.programs:id')
            ->whereIn('status', ['open', 'active'])
            ->orderBy('code')
            ->get();

        foreach (Student::query()->with('currentProgram')->where('status', Student::STATUS_ACTIVE)->get() as $student) {
            $programId = $student->currentProgram?->program_id;

            foreach ($sections as $section) {
                $inCurriculum = $section->offering->course->programs->contains('id', $programId);

                if (! $inCurriculum) {
                    continue;
                }

                try {
                    $enrollments->enroll($student, $section);
                } catch (BusinessRuleException|ValidationException) {
                    // Refused by a rule (or already enrolled): skip.
                }
            }
        }
    }
}
