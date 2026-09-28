<?php

namespace Database\Factories;

use App\Enums\AcademicYearStatus;
use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    public function definition(): array
    {
        $startYear = fake()->numberBetween(2020, 2030);

        return [
            'code' => $startYear.'-'.($startYear + 1),
            'name' => 'Academic Year '.$startYear.'-'.($startYear + 1),
            'start_date' => $startYear.'-09-01',
            'end_date' => ($startYear + 1).'-08-31',
            'status' => AcademicYearStatus::Planned,
            'is_current' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => AcademicYearStatus::Active]);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => AcademicYearStatus::Completed]);
    }

    public function current(): static
    {
        return $this->active()->state(fn () => ['is_current' => true]);
    }
}
