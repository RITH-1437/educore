<?php

namespace Database\Factories;

use App\Models\University;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<University>
 */
class UniversityFactory extends Factory
{
    public function definition(): array
    {
        $code = strtoupper(fake()->unique()->lexify('UNI???'));

        return [
            'code' => $code,
            'name' => fake()->company().' University',
            'short_name' => strtoupper(fake()->lexify('???')),
            'address' => fake()->address(),
            'phone' => fake()->numerify('+855 ## ### ###'),
            'email' => fake()->unique()->safeEmail(),
            'logo_key' => null,
            'website' => 'https://'.fake()->domainName(),
            'is_current' => false,
        ];
    }

    public function current(): static
    {
        return $this->state(fn () => ['is_current' => true]);
    }
}
