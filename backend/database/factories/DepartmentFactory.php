<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\University;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'university_id' => University::factory(),
            'code' => strtoupper(fake()->unique()->lexify('DEP???')),
            'name' => 'Department of '.fake()->unique()->words(2, true),
            'head_name' => fake()->name(),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
