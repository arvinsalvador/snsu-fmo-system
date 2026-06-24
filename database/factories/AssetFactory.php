<?php

namespace Database\Factories;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Asset> */
class AssetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'asset_tag' => 'AST-'.fake()->unique()->numerify('####'),
            'name' => fake()->words(3, true),
            'location' => fake()->randomElement(['Admin Building', 'Library', 'Engineering Lab', 'Gymnasium']),
            'brand' => fake()->optional()->company(),
            'model' => fake()->optional()->bothify('MDL-###'),
            'serial_number' => fake()->optional()->bothify('SN-########'),
            'purchase_date' => fake()->optional()->dateTimeBetween('-5 years', '-1 month')?->format('Y-m-d'),
            'warranty_until' => fake()->optional()->dateTimeBetween('now', '+3 years')?->format('Y-m-d'),
            'status' => 'active',
            'remarks' => fake()->optional()->sentence(),
        ];
    }
}
