<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\University;
use App\Services\UniversityService;
use Illuminate\Database\Seeder;

/**
 * Test fixture: the top of the academic hierarchy — one current university
 * and six departments directly under it (no faculty level since report 39).
 *
 * Deterministic and idempotent per `docs/database/seed-strategy.md`: the
 * university and departments upsert by `code`, so re-running never duplicates
 * rows.
 */
class UniversityStructureSeeder extends Seeder
{
    /**
     * The departments of the fixture university.
     *
     * @var list<array{code: string, name: string, head: string}>
     */
    private const DEPARTMENTS = [
        ['code' => 'CSE', 'name' => 'Department of Computer Science and Engineering', 'head' => 'Dr. Dara Lim'],
        ['code' => 'EEE', 'name' => 'Department of Electrical and Electronics Engineering', 'head' => 'Dr. Bopha Nou'],
        ['code' => 'PHY', 'name' => 'Department of Physics', 'head' => 'Dr. Vichea Sim'],
        ['code' => 'MTH', 'name' => 'Department of Mathematics', 'head' => 'Dr. Arun Sam'],
        ['code' => 'ENG-L', 'name' => 'Department of English and Linguistics', 'head' => 'Dr. Sreypov Sok'],
        ['code' => 'ECO', 'name' => 'Department of Economics', 'head' => 'Dr. Chanthou Prak'],
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

        foreach (self::DEPARTMENTS as $definition) {
            Department::query()->updateOrCreate(
                ['code' => $definition['code']],
                [
                    'university_id' => $university->id,
                    'name' => $definition['name'],
                    'head_name' => $definition['head'],
                    'description' => null,
                    'is_active' => true,
                ],
            );
        }
    }
}
