<?php

namespace Database\Factories;

use App\Enums\Role as RoleSlug;
use App\Models\Program;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            // Reuse the single `student` role (role slugs are unique).
            'user_id' => fn () => User::factory()->create([
                'role_id' => Role::query()->firstOrCreate(
                    ['slug' => RoleSlug::Student->value],
                    ['name' => 'Student', 'is_system' => true],
                )->id,
            ])->id,
            'student_number' => strtoupper(fake()->unique()->bothify('STU-####-####')),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'gender' => fake()->randomElement(Student::GENDERS),
            'date_of_birth' => fake()->dateTimeBetween('-26 years', '-18 years')->format('Y-m-d'),
            'enrollment_date' => '2025-09-01',
            'status' => Student::STATUS_ACTIVE,
        ];
    }

    /**
     * Give the student an active program period.
     */
    public function inProgram(?Program $program = null, string $startedOn = '2025-09-01'): static
    {
        return $this->afterCreating(function (Student $student) use ($program, $startedOn) {
            StudentProgram::query()->create([
                'student_id' => $student->id,
                'program_id' => ($program ?? Program::factory()->create())->id,
                'started_on' => $startedOn,
                'status' => StudentProgram::STATUS_ACTIVE,
            ]);
        });
    }

    public function withStatus(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
