<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetMaintenanceRecord;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AssetMaintenanceRecord> */
class AssetMaintenanceRecordFactory extends Factory
{
    protected $model = AssetMaintenanceRecord::class;

    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'maintenance_schedule_id' => null,
            'work_order_id' => null,
            'staff_profile_id' => StaffProfile::factory(),
            'completed_by' => User::factory(),
            'completion_date' => now()->subDays(fake()->numberBetween(0, 30))->toDateString(),
            'findings' => fake()->sentence(),
            'actions_taken' => fake()->paragraph(),
            'remarks' => fake()->optional()->sentence(),
            'labor_cost' => fake()->optional()->randomFloat(2, 0, 5000),
        ];
    }
}
