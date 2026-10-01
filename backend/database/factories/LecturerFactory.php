<?php

namespace Database\Factories;

use App\Enums\Role as RoleSlug;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lecturer>
 */
class LecturerFactory extends Factory
{
    public function definition(): array
    {
        return [
            // Reuse the single `lecturer` role instead of minting a new one per
            // lecturer (role slugs are unique).
            'user_id' => fn () => User::factory()->create([
                'role_id' => Role::query()->firstOrCreate(
                    ['slug' => RoleSlug::Lecturer->value],
                    ['name' => 'Lecturer', 'is_system' => true],
                )->id,
            ])->id,
            'staff_number' => strtoupper(fake()->unique()->bothify('LEC-####')),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'title' => 'Dr.',
            'department_id' => Department::factory(),
            'position' => 'Lecturer',
            'specialization' => fake()->words(2, true),
            'employment_type' => 'full_time',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
