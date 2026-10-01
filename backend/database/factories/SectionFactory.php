<?php

namespace Database\Factories;

use App\Models\CourseOffering;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_offering_id' => CourseOffering::factory(),
            'code' => strtoupper(fake()->unique()->bothify('?#')),
            'name' => null,
            'capacity' => 40,
            'status' => 'draft',
        ];
    }
}
