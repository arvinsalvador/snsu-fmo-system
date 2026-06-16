<?php

namespace Database\Factories;

use App\Models\Building;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Building>
 */
class BuildingFactory extends Factory
{
    protected $model = Building::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('BLDG-###'),
            'name' => fake()->unique()->company().' Building',
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
