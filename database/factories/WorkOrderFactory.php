<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Department;
use App\Models\Floor;
use App\Models\Priority;
use App\Models\Room;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderCategory;
use App\Models\WorkOrderStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WorkOrder>
 */
class WorkOrderFactory extends Factory
{
    protected $model = WorkOrder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'work_order_number' => 'WO-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'requestor_id' => User::factory(),
            'department_id' => Department::factory(),
            'building_id' => Building::factory(),
            'floor_id' => Floor::factory(),
            'room_id' => Room::factory(),
            'category_id' => WorkOrderCategory::factory(),
            'priority_id' => Priority::factory(),
            'status_id' => WorkOrderStatus::factory(),
            'preferred_staff_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'requested_at' => now(),
            'target_completion_date' => now()->addDays(7)->toDateString(),
            'completed_at' => null,
        ];
    }
}
