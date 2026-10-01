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
        // Unique, wide range: `code` is unique, and tests that build several
        // years (one per semester factory) collided with the old 11-year range.
        $startYear = fake()->unique()->numberBetween(1950, 2099);

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
