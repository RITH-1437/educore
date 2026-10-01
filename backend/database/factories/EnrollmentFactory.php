<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Builds a raw enrollment row. Tests that exercise the rules go through
 * `EnrollmentService`; this factory only sets up history.
 *
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'section_id' => Section::factory(),
            'academic_year_id' => fn (array $attributes) => Section::query()->find($attributes['section_id'])->offering->semester->academic_year_id,
            'semester_id' => fn (array $attributes) => Section::query()->find($attributes['section_id'])->offering->semester_id,
            'status' => Enrollment::STATUS_CONFIRMED,
            'enrolled_at' => now(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => Enrollment::STATUS_COMPLETED]);
    }
}
