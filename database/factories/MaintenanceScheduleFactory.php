<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\MaintenanceSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MaintenanceSchedule> */
class MaintenanceScheduleFactory extends Factory
{
    protected $model = MaintenanceSchedule::class;

    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'title' => fake()->randomElement(['Electrical inspection', 'HVAC cleaning', 'Safety calibration', 'General servicing']),
            'description' => fake()->sentence(),
            'frequency' => fake()->randomElement(array_keys(MaintenanceSchedule::FREQUENCIES)),
            'next_due_date' => now()->addDays(fake()->numberBetween(1, 60))->toDateString(),
            'last_completed_date' => now()->subDays(fake()->numberBetween(30, 120))->toDateString(),
            'is_active' => true,
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn (): array => [
            'next_due_date' => now()->subDays(5)->toDateString(),
        ]);
    }

    public function upcoming(): static
    {
        return $this->state(fn (): array => [
            'next_due_date' => now()->addDays(7)->toDateString(),
        ]);
    }
}
