<?php

namespace Database\Factories;

use App\Models\Priority;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Priority>
 */
class PriorityFactory extends Factory
{
    protected $model = Priority::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'level' => fake()->unique()->numberBetween(10, 250),
            'color' => 'blue',
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
