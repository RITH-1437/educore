<?php

namespace Database\Factories;

use App\Models\ErrorLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ErrorLog>
 */
class ErrorLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => null,
            'status_code' => 404,
            'method' => fake()->randomElement(['GET', 'GET', 'GET', 'POST']),
            'url' => '/'.fake()->unique()->slug(2).'/'.fake()->unique()->numerify('#####'),
            'route_name' => null,
            'exception_class' => null,
            'message' => null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'context' => null,
            'created_at' => now(),
        ];
    }

    /**
     * A missing route or record — the common case.
     */
    public function notFound(): static
    {
        return $this->state(fn () => [
            'status_code' => 404,
            'exception_class' => null,
            'message' => null,
        ]);
    }

    /**
     * An unhandled failure. `exception_class` is what makes these diagnosable.
     */
    public function serverError(int $status = 500): static
    {
        return $this->state(fn () => [
            'status_code' => $status,
            'exception_class' => fake()->randomElement([
                'Symfony\\Component\\HttpKernel\\Exception\\ServiceUnavailableHttpException',
                'Illuminate\\Database\\QueryException',
                'RuntimeException',
            ]),
            'message' => fake()->sentence(),
        ]);
    }

    /**
     * Attributed to a signed-in user.
     */
    public function forUser(?User $user): static
    {
        return $this->state(fn () => ['user_id' => $user?->id]);
    }
}
