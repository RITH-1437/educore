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
            // Programs hang off departments, so they follow the structure.
            ProgramSeeder::class,
            // Courses (and curricula) need both departments and programs.
            CourseSeeder::class,
            // Lecturer accounts + profiles need the Lecturer role and departments.
            LecturerSeeder::class,
            // Students need the Student role and active programs.
            StudentSeeder::class,
            AcademicYearSeeder::class,
            // Offerings/sections need courses, lecturers and the semesters above.
            CourseOfferingSeeder::class,
            // Rooms and weekly meetings before enrollment, so student clashes apply.
            RoomSeeder::class,
            ScheduleSeeder::class,
            // Enrollments go through EnrollmentService, so every rule applies.
            EnrollmentSeeder::class,
            // Operational diagnostics last: this table is filled by the recorder
            // at runtime, so seeding it after the structure keeps the rows
            // internally consistent.
            ErrorLogSeeder::class,
        ]);
    }
}
