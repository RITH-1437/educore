<?php

namespace Tests;

use App\Enums\Role;
use App\Models\Department;
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

    // ------------------------------------------- Department Admin unit scoping ---

    /** A Department Admin assigned to the department (`docs/39_Department-Only-Structure-Report.md`). */
    protected function departmentAdminFor(int|Department|null $department): User
    {
        $role = RoleModel::query()->firstWhere('slug', Role::DepartmentAdmin->value)
            ?? RoleModel::factory()->withSlug(Role::DepartmentAdmin->value)->create();

        return User::factory()->create(['role_id' => $role->id, 'department_id' => $department instanceof Department ? $department->id : $department]);
    }

    /** The department that owns a section (through its course). */
    protected function departmentOfSection(Section $section): int
    {
        return (int) $section->offering->course->department_id;
    }

    /** Give the student an active program in the department (a new program). */
    protected function placeInDepartment(Student $student, int|Department $department): void
    {
        $departmentId = $department instanceof Department ? $department->id : $department;
        $program = Program::factory()->create(['department_id' => $departmentId]);
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
