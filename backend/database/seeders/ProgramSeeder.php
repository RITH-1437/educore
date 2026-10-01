<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Program;
use Illuminate\Database\Seeder;

/**
 * Seeds one or two degree programs for each seeded department.
 *
 * Deterministic and idempotent (`docs/database/seed-strategy.md`): programs
 * upsert by their unique `code`. Departments come from
 * `UniversityStructureSeeder`, which must run first; a missing department is
 * skipped rather than created here.
 */
class ProgramSeeder extends Seeder
{
    /**
     * @var list<array{department: string, code: string, name: string, level: string, years: int, credits: float}>
     */
    private const PROGRAMS = [
        ['department' => 'CSE', 'code' => 'BSCS', 'name' => 'Bachelor of Computer Science', 'level' => 'bachelor', 'years' => 4, 'credits' => 144.0],
        ['department' => 'CSE', 'code' => 'MSCS', 'name' => 'Master of Computer Science', 'level' => 'master', 'years' => 2, 'credits' => 48.0],
        ['department' => 'EEE', 'code' => 'BEEE', 'name' => 'Bachelor of Electrical and Electronics Engineering', 'level' => 'bachelor', 'years' => 5, 'credits' => 160.0],
        ['department' => 'PHY', 'code' => 'BSPHY', 'name' => 'Bachelor of Science in Physics', 'level' => 'bachelor', 'years' => 4, 'credits' => 130.0],
        ['department' => 'MTH', 'code' => 'BSMTH', 'name' => 'Bachelor of Science in Mathematics', 'level' => 'bachelor', 'years' => 4, 'credits' => 128.0],
        ['department' => 'ENG-L', 'code' => 'BAENG', 'name' => 'Bachelor of Arts in English', 'level' => 'bachelor', 'years' => 4, 'credits' => 124.0],
        ['department' => 'ECO', 'code' => 'BAECO', 'name' => 'Bachelor of Arts in Economics', 'level' => 'bachelor', 'years' => 4, 'credits' => 126.0],
    ];

    public function run(): void
    {
        foreach (self::PROGRAMS as $definition) {
            $department = Department::query()->where('code', $definition['department'])->first();

            if ($department === null) {
                continue;
            }

            Program::query()->updateOrCreate(
                ['code' => $definition['code']],
                [
                    'department_id' => $department->id,
                    'name' => $definition['name'],
                    'degree_level' => $definition['level'],
                    'duration_years' => $definition['years'],
                    'credits_required' => $definition['credits'],
                    'is_active' => true,
                ],
            );
        }
    }
}
