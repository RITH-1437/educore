<?php

namespace Database\Factories;

use App\Enums\SemesterStatus;
use App\Models\AcademicYear;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Semester>
 */
class SemesterFactory extends Factory
{
    public function definition(): array
    {
        $sequence = fake()->numberBetween(1, 3);
        $name = 'Semester '.$sequence;

        return [
            'academic_year_id' => AcademicYear::factory(),
            'name' => $name,
            'code' => 'S'.$sequence,
            'sequence' => $sequence,
            'start_date' => null,
            'end_date' => null,
            'enrollment_start' => null,
            'enrollment_end' => null,
            'exam_start' => null,
            'exam_end' => null,
            'status' => SemesterStatus::Planned,
        ];
    }

    public function forYear(AcademicYear $academicYear): static
    {
        return $this->state(fn () => ['academic_year_id' => $academicYear->id]);
    }

    public function open(): static
    {
        return $this->state(fn () => ['status' => SemesterStatus::Open]);
    }
}
