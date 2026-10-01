<?php

namespace Database\Factories;

use App\Models\Faculty;
use App\Models\University;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faculty>
 */
class FacultyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'university_id' => University::factory(),
            'code' => strtoupper(fake()->unique()->lexify('FAC???')),
            'name' => 'Faculty of '.fake()->unique()->word(),
            'dean_name' => fake()->name(),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
