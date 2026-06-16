<?php

namespace Database\Factories;

use App\Models\WorkOrderStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkOrderStatus>
 */
class WorkOrderStatusFactory extends Factory
{
    protected $model = WorkOrderStatus::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'sort_order' => fake()->unique()->numberBetween(10, 250),
            'is_terminal' => false,
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
