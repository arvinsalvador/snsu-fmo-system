<?php

namespace Database\Factories;

use App\Models\WorkOrderCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkOrderCategory>
 */
class WorkOrderCategoryFactory extends Factory
{
    protected $model = WorkOrderCategory::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
