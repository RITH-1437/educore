<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseGradingConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseGradingConfig>
 */
class CourseGradingConfigFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'attendance_weight' => 10,
            'assignment_weight' => 25,
            'midterm_weight' => 20,
            'final_weight' => 40,
            'practical_weight' => 5,
        ];
    }
}
