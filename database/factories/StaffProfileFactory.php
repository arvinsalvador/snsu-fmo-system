<?php

namespace Database\Factories;

use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaffProfile>
 */
class StaffProfileFactory extends Factory
{
    protected $model = StaffProfile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'employee_code' => fake()->unique()->bothify('FMO-####'),
            'position' => fake()->jobTitle(),
            'designation' => 'FMO Staff',
            'employment_status' => 'active',
            'availability_status' => 'available',
            'remarks' => null,
        ];
    }
}
