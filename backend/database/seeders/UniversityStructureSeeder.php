<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\University;
use App\Services\UniversityService;
use Illuminate\Database\Seeder;

/**
 * Seeds the top of the academic hierarchy: one current university, three
 * faculties, and two departments per faculty.
 *
 * Deterministic and idempotent per `docs/database/seed-strategy.md`: faculties
 * upsert by `code`, departments by `(faculty_id, code)`, so re-running never
 * duplicates rows.
 */
class UniversityStructureSeeder extends Seeder
{
    /**
     * Faculty definitions, each with its departments.
     *
     * @var list<array{code: string, name: string, dean: string, departments: list<array{code: string, name: string, head: string}>}>
     */
    private const STRUCTURE = [
        [
            'code' => 'ENG',
            'name' => 'Faculty of Engineering',
            'dean' => 'Dr. Sokha Chan',
            'departments' => [
                ['code' => 'CSE', 'name' => 'Department of Computer Science and Engineering', 'head' => 'Dr. Dara Lim'],
                ['code' => 'EEE', 'name' => 'Department of Electrical and Electronics Engineering', 'head' => 'Dr. Bopha Nou'],
            ],
        ],
        [
            'code' => 'SCI',
            'name' => 'Faculty of Science',
            'dean' => 'Dr. Rithy Chea',
            'departments' => [
                ['code' => 'PHY', 'name' => 'Department of Physics', 'head' => 'Dr. Vichea Sim'],
                ['code' => 'MTH', 'name' => 'Department of Mathematics', 'head' => 'Dr. Arun Sam'],
            ],
        ],
        [
            'code' => 'HSS',
            'name' => 'Faculty of Humanities and Social Sciences',
            'dean' => 'Dr. Keo Sreymom',
            'departments' => [
                ['code' => 'ENG-L', 'name' => 'Department of English and Linguistics', 'head' => 'Dr. Sreypov Sok'],
                ['code' => 'ECO', 'name' => 'Department of Economics', 'head' => 'Dr. Chanthou Prak'],
            ],
        ],
    ];

    public function run(): void
    {
        $university = University::query()->updateOrCreate(
            ['code' => 'ITC'],
            [
                'name' => 'Institute of Technology Cambodia',
                'short_name' => 'ITC',
                'address' => 'Prey Saem District, Kandal Province, Cambodia',
                'phone' => '+855 23 883 222',
                'email' => 'info@educore.kh',
                'website' => 'https://www.educore.kh',
            ],
        );

        // `is_current` is a single-row invariant owned by `UniversityService`,
        // not a plain column, so promoting ITC goes through the service. That
        // clears the flag on any previously current university instead of
        // leaving two rows claiming to be current.
        app(UniversityService::class)->makeCurrent($university);

        foreach (self::STRUCTURE as $facultyDefinition) {
            $faculty = Faculty::query()->updateOrCreate(
                ['code' => $facultyDefinition['code']],
                [
                    'university_id' => $university->id,
                    'name' => $facultyDefinition['name'],
                    'dean_name' => $facultyDefinition['dean'],
                    'description' => null,
                    'is_active' => true,
                ],
            );

            foreach ($facultyDefinition['departments'] as $departmentDefinition) {
                Department::query()->updateOrCreate(
                    ['faculty_id' => $faculty->id, 'code' => $departmentDefinition['code']],
                    [
                        'name' => $departmentDefinition['name'],
                        'head_name' => $departmentDefinition['head'],
                        'description' => null,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
