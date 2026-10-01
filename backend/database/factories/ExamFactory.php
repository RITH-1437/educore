<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exam>
 */
class ExamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'exam_type' => 'midterm',
            'title' => 'Midterm exam',
            'weight' => 30,
            'max_score' => 100,
            'scheduled_date' => null,
            'start_time' => null,
            'end_time' => null,
            'location' => null,
            'is_published' => false,
        ];
    }

    public function released(): static
    {
        return $this->state(fn () => ['is_published' => true]);
    }
}
