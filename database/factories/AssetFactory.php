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
            'status' => 'active',
        ];
    }
}
