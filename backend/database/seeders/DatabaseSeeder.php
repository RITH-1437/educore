<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            // Faculties belong to a university, so the structure is seeded
            // before the academic calendar that sits alongside it.
            UniversityStructureSeeder::class,
            AcademicYearSeeder::class,
        ]);
    }
}
