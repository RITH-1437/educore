<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'code' => strtoupper(fake()->unique()->lexify('CRS???')),
            'name' => ucfirst(fake()->unique()->words(3, true)),
            'credits' => 3,
            'lecture_hours' => 30,
            'lab_hours' => 15,
            'description' => fake()->sentence(),
            'course_level' => 'introductory',
            'status' => Course::STATUS_ACTIVE,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => Course::STATUS_DRAFT]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['status' => Course::STATUS_ARCHIVED]);
    }
}
