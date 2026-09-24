<?php

namespace Database\Factories;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    public function definition(): array
    {
        $slug = Str::slug(fake()->unique()->words(2, true));

        return [
            'name' => Str::headline($slug),
            'slug' => $slug,
            'description' => null,
            'is_system' => false,
        ];
    }

    public function withSlug(string $slug): static
    {
        return $this->state(fn () => [
            'name' => Str::headline(str_replace('-', ' ', $slug)),
            'slug' => $slug,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->withSlug(Role::SuperAdmin->value)->state(fn () => ['is_system' => true]);
    }
}
