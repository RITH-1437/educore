<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

/**
 * Seeds campus rooms; idempotent by unique `code`.
 */
class RoomSeeder extends Seeder
{
    /** @var list<array{0: string, 1: string, 2: string, 3: string, 4: int, 5: string}> */
    private const ROOMS = [
        ['A-101', 'Lecture Hall A101', 'Building A', '1', 120, 'lecture'],
        ['A-102', 'Lecture Hall A102', 'Building A', '1', 80, 'lecture'],
        ['B-201', 'Classroom B201', 'Building B', '2', 50, 'lecture'],
        ['B-202', 'Classroom B202', 'Building B', '2', 50, 'lecture'],
        ['C-LAB1', 'Computer Lab 1', 'Building C', 'G', 40, 'lab'],
        ['C-LAB2', 'Electronics Lab', 'Building C', 'G', 35, 'lab'],
        ['D-SEM1', 'Seminar Room D1', 'Building D', '3', 25, 'seminar'],
    ];

    public function run(): void
    {
        foreach (self::ROOMS as [$code, $name, $building, $floor, $capacity, $type]) {
            Room::query()->updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'building' => $building, 'floor' => $floor, 'capacity' => $capacity, 'room_type' => $type, 'is_active' => true],
            );
        }
    }
}
