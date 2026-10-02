<?php

namespace Tests;

use App\Enums\Role;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Role as RoleModel;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentProgram;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    // ------------------------------------------- Faculty Admin unit scoping ---

    /** A Faculty Admin assigned to the faculty (`docs/32_Faculty-Admin-Scoping-Report.md`). */
    protected function facultyAdminFor(int|Faculty|null $faculty): User
    {
        $role = RoleModel::query()->firstWhere('slug', Role::FacultyAdmin->value)
            ?? RoleModel::factory()->withSlug(Role::FacultyAdmin->value)->create();

        return User::factory()->create(['role_id' => $role->id, 'faculty_id' => $faculty instanceof Faculty ? $faculty->id : $faculty]);
    }

    /** The faculty that owns a section (through its course's department). */
    protected function facultyOfSection(Section $section): int
    {
        return (int) $section->offering->course->department->faculty_id;
    }

    /** Give the student an active program in the faculty (a new department + program). */
    protected function placeInFaculty(Student $student, int|Faculty $faculty): void
    {
        $facultyId = $faculty instanceof Faculty ? $faculty->id : $faculty;
        $program = Program::factory()->create(['department_id' => Department::factory()->create(['faculty_id' => $facultyId])->id]);
        StudentProgram::query()->create(['student_id' => $student->id, 'program_id' => $program->id, 'started_on' => now()->toDateString(), 'status' => StudentProgram::STATUS_ACTIVE]);
    }

    /**
     * Safety net for `RefreshDatabase`: never wipe a database that is not a
     * dedicated test database.
     *
     * When the configuration is cached (`php artisan optimize`), the
     * `DB_DATABASE=educore_test` override in phpunit.xml is ignored and the
     * suite would otherwise reset the development database.
     *
     * @return array<class-string, class-string>
     */
    protected function setUpTraits()
    {
        $connection = config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if (! str_ends_with($database, '_test') && $database !== ':memory:') {
            throw new RuntimeException(
                "Refusing to refresh database [{$database}]: tests must run against a *_test database. "
                .'If the configuration is cached, run `php artisan optimize:clear` first.'
            );
        }

        return parent::setUpTraits();
    }
}
