<?php

namespace Database\Seeders;

use App\Enums\SemesterStatus;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Lecturer;
use App\Models\Section;
use App\Models\Semester;
use Illuminate\Database\Seeder;

/**
 * Seeds offerings with sections and lecturer assignments for the open
 * semester (or the first not-completed one).
 *
 * Deterministic and idempotent: offerings upsert by `(course_id, semester_id)`,
 * sections by `(course_offering_id, code)`, assignments use
 * `syncWithoutDetaching`. Missing courses/lecturers are skipped.
 */
class CourseOfferingSeeder extends Seeder
{
    /**
     * course code => [sections => capacity], lecturer staff numbers (first = primary).
     *
     * @var array<string, array{sections: array<string, int>, lecturers: list<string>}>
     */
    private const PLAN = [
        'CS101' => ['sections' => ['A' => 40, 'B' => 40], 'lecturers' => ['LEC-0003', 'LEC-0002']],
        'CS201' => ['sections' => ['A' => 35], 'lecturers' => ['LEC-0001']],
        'CS202' => ['sections' => ['A' => 35], 'lecturers' => ['LEC-0002']],
        'MA101' => ['sections' => ['A' => 60, 'B' => 60], 'lecturers' => ['LEC-0005']],
        'PH101' => ['sections' => ['A' => 50], 'lecturers' => ['LEC-0006']],
        'EE101' => ['sections' => ['A' => 40], 'lecturers' => ['LEC-0004']],
    ];

    public function run(): void
    {
        $semester = Semester::query()->where('status', SemesterStatus::Open->value)->orderByDesc('academic_year_id')->first()
            ?? Semester::query()->where('status', '!=', SemesterStatus::Completed->value)->orderBy('academic_year_id')->orderBy('sequence')->first();

        if ($semester === null) {
            return;
        }

        foreach (self::PLAN as $courseCode => $plan) {
            $course = Course::query()->where('code', $courseCode)->where('status', Course::STATUS_ACTIVE)->first();

            if ($course === null) {
                continue;
            }

            $offering = CourseOffering::query()->updateOrCreate(
                ['course_id' => $course->id, 'semester_id' => $semester->id],
                ['status' => 'open'],
            );

            foreach ($plan['sections'] as $code => $capacity) {
                $section = Section::query()->updateOrCreate(
                    ['course_offering_id' => $offering->id, 'code' => $code],
                    ['capacity' => $capacity, 'status' => 'open'],
                );

                // Spread lecturers over sections: section A gets the first as
                // primary; further sections rotate through the list.
                $index = array_search($code, array_keys($plan['sections']), true);
                $staff = $plan['lecturers'][$index % count($plan['lecturers'])];
                $lecturer = Lecturer::query()->where('staff_number', $staff)->where('is_active', true)->first();

                if ($lecturer !== null && ! $section->lecturers()->wherePivot('role', 'primary')->exists()) {
                    $section->lecturers()->syncWithoutDetaching([$lecturer->id => ['role' => 'primary']]);
                }
            }
        }
    }
}
