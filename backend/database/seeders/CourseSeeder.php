<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Department;
use App\Models\Program;
use Illuminate\Database\Seeder;

/**
 * Seeds a small, internally consistent catalog: courses per department, a
 * prerequisite chain, and curricula for the seeded programs.
 *
 * Deterministic and idempotent (`docs/database/seed-strategy.md`): courses
 * upsert by their unique `code`; prerequisite and curriculum links use
 * `syncWithoutDetaching`, so re-running never duplicates them. Departments and
 * programs come from `UniversityStructureSeeder` / `ProgramSeeder`; anything
 * missing is skipped, never created here. The prerequisite graph below is
 * acyclic by construction.
 */
class CourseSeeder extends Seeder
{
    /**
     * @var list<array{department: string, code: string, name: string, credits: float, level: string, lecture: int, lab: int}>
     */
    private const COURSES = [
        ['department' => 'CSE', 'code' => 'CS101', 'name' => 'Introduction to Programming', 'credits' => 3.0, 'level' => 'introductory', 'lecture' => 30, 'lab' => 30],
        ['department' => 'CSE', 'code' => 'CS201', 'name' => 'Data Structures', 'credits' => 3.0, 'level' => 'intermediate', 'lecture' => 30, 'lab' => 30],
        ['department' => 'CSE', 'code' => 'CS202', 'name' => 'Database Systems', 'credits' => 3.0, 'level' => 'intermediate', 'lecture' => 30, 'lab' => 15],
        ['department' => 'CSE', 'code' => 'CS301', 'name' => 'Algorithms', 'credits' => 3.0, 'level' => 'advanced', 'lecture' => 45, 'lab' => 0],
        ['department' => 'CSE', 'code' => 'CS401', 'name' => 'Compiler Design', 'credits' => 4.0, 'level' => 'advanced', 'lecture' => 45, 'lab' => 15],
        ['department' => 'MTH', 'code' => 'MA101', 'name' => 'Calculus I', 'credits' => 4.0, 'level' => 'introductory', 'lecture' => 60, 'lab' => 0],
        ['department' => 'MTH', 'code' => 'MA102', 'name' => 'Discrete Mathematics', 'credits' => 3.0, 'level' => 'introductory', 'lecture' => 45, 'lab' => 0],
        ['department' => 'PHY', 'code' => 'PH101', 'name' => 'General Physics I', 'credits' => 4.0, 'level' => 'introductory', 'lecture' => 45, 'lab' => 30],
        ['department' => 'EEE', 'code' => 'EE101', 'name' => 'Circuit Analysis', 'credits' => 3.0, 'level' => 'introductory', 'lecture' => 45, 'lab' => 15],
        ['department' => 'ECO', 'code' => 'EC101', 'name' => 'Principles of Economics', 'credits' => 3.0, 'level' => 'introductory', 'lecture' => 45, 'lab' => 0],
    ];

    /** course code => prerequisite codes. */
    private const PREREQUISITES = [
        'CS201' => ['CS101'],
        'CS202' => ['CS101'],
        'CS301' => ['CS201', 'MA102'],
        'CS401' => ['CS301'],
    ];

    /** program code => [course code => [required, suggested semester]]. */
    private const CURRICULA = [
        'BSCS' => [
            'CS101' => [true, 1], 'MA101' => [true, 1], 'MA102' => [true, 2],
            'CS201' => [true, 3], 'CS202' => [true, 4], 'CS301' => [true, 5], 'CS401' => [false, 7],
        ],
        'BEEE' => ['EE101' => [true, 1], 'MA101' => [true, 1], 'PH101' => [true, 2]],
        'BSPHY' => ['PH101' => [true, 1], 'MA101' => [true, 1]],
        'BSMTH' => ['MA101' => [true, 1], 'MA102' => [true, 2]],
        'BAECO' => ['EC101' => [true, 1]],
    ];

    public function run(): void
    {
        foreach (self::COURSES as $definition) {
            $department = Department::query()->where('code', $definition['department'])->first();

            if ($department === null) {
                continue;
            }

            Course::query()->updateOrCreate(
                ['code' => $definition['code']],
                [
                    'department_id' => $department->id,
                    'name' => $definition['name'],
                    'credits' => $definition['credits'],
                    'lecture_hours' => $definition['lecture'],
                    'lab_hours' => $definition['lab'],
                    'course_level' => $definition['level'],
                    'status' => Course::STATUS_ACTIVE,
                ],
            );
        }

        foreach (self::PREREQUISITES as $code => $prerequisiteCodes) {
            $course = Course::query()->where('code', $code)->first();
            $ids = Course::query()->whereIn('code', $prerequisiteCodes)->pluck('id')->all();

            if ($course !== null && $ids !== []) {
                $course->prerequisites()->syncWithoutDetaching(
                    array_fill_keys($ids, ['is_strict' => true]),
                );
            }
        }

        foreach (self::CURRICULA as $programCode => $placements) {
            $program = Program::query()->where('code', $programCode)->first();

            if ($program === null) {
                continue;
            }

            foreach ($placements as $courseCode => [$required, $semester]) {
                $course = Course::query()->where('code', $courseCode)->first();

                if ($course !== null) {
                    $program->courses()->syncWithoutDetaching([
                        $course->id => ['is_required' => $required, 'suggested_semester' => $semester],
                    ]);
                }
            }
        }
    }
}
