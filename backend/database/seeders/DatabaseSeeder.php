<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Only what a fresh installation needs: the roles, the single Super Admin
     * account, and the system configuration that has no create screen in the
     * UI. No demo people or records are seeded — everything else is entered
     * through the application.
     *
     * The other seeder classes in this folder (UniversityStructureSeeder,
     * ProgramSeeder, CourseSeeder, ...) are not run here; the feature tests use
     * them as fixtures.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            // The one account: Super Admin (admin@educore.kh).
            UserSeeder::class,
            // The grading scale grades are mapped against (module 9.14); the UI edits it but cannot create it.
            GradingScaleSeeder::class,
            // Requestable official documents (module 9.16); there is no screen to create types.
            DocumentTypeSeeder::class,
        ]);
    }
}
