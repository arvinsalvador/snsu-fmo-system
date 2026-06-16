<?php

namespace Database\Factories;

use App\Models\Floor;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'floor_id' => Floor::factory(),
            'room_name' => fake()->unique()->bothify('Room ###'),
            'room_code' => fake()->unique()->bothify('RM-###'),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
