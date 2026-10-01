<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('R-###')),
            'name' => 'Room '.fake()->unique()->numerify('###'),
            'building' => 'Building A',
            'floor' => '1',
            'capacity' => 60,
            'room_type' => 'lecture',
            'is_active' => true,
        ];
    }
}
