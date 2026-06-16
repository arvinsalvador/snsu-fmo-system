<?php

namespace Database\Seeders;

use App\Models\AssetCategory;
use App\Models\Building;
use App\Models\Department;
use App\Models\Floor;
use App\Models\InventoryCategory;
use App\Models\MaintenanceType;
use App\Models\Priority;
use App\Models\Room;
use App\Models\WorkOrderCategory;
use App\Models\WorkOrderStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedLocations();
        $this->seedDepartments();
        $this->seedWorkOrderCategories();
        $this->seedPriorities();
        $this->seedWorkOrderStatuses();
        $this->seedAssetCategories();
        $this->seedMaintenanceTypes();
        $this->seedInventoryCategories();
    }

    private function seedLocations(): void
    {
        $building = Building::query()->firstOrCreate(
            ['code' => 'MAIN'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Main Building',
                'description' => 'Primary campus building.',
                'is_active' => true,
            ],
        );

        $floor = Floor::query()->firstOrCreate(
            [
                'building_id' => $building->id,
                'floor_name' => 'Ground Floor',
            ],
            [
                'uuid' => (string) Str::uuid(),
                'description' => 'Ground floor of the Main Building.',
                'is_active' => true,
            ],
        );

        Room::query()->firstOrCreate(
            [
                'floor_id' => $floor->id,
                'room_code' => 'MAIN-GF-001',
            ],
            [
                'uuid' => (string) Str::uuid(),
                'room_name' => 'Lobby',
                'description' => 'Main lobby.',
                'is_active' => true,
            ],
        );
    }

    private function seedDepartments(): void
    {
        $this->seedCodeNameRecords(Department::class, [
            ['code' => 'FMO', 'name' => 'Facilities Management Office'],
            ['code' => 'CDO', 'name' => 'Campus Director Office'],
            ['code' => 'DI', 'name' => 'Director for Instruction Office'],
            ['code' => 'ADMIN', 'name' => 'Administration Office'],
        ]);
    }

    private function seedWorkOrderCategories(): void
    {
        $this->seedNameRecords(WorkOrderCategory::class, [
            'Carpentry',
            'Electrical',
            'Plumbing',
            'Painting',
            'Air Conditioning',
            'Civil Works',
            'Others',
        ]);
    }

    private function seedPriorities(): void
    {
        $priorities = [
            ['name' => 'Low', 'level' => 1, 'color' => 'gray'],
            ['name' => 'Normal', 'level' => 2, 'color' => 'blue'],
            ['name' => 'High', 'level' => 3, 'color' => 'orange'],
            ['name' => 'Urgent', 'level' => 4, 'color' => 'red'],
        ];

        foreach ($priorities as $priority) {
            Priority::query()->firstOrCreate(
                ['name' => $priority['name']],
                [
                    'uuid' => (string) Str::uuid(),
                    'level' => $priority['level'],
                    'color' => $priority['color'],
                    'description' => "{$priority['name']} work order priority.",
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedWorkOrderStatuses(): void
    {
        $statuses = [
            'Draft',
            'Submitted',
            'For Approval',
            'Approved',
            'Assigned',
            'In Progress',
            'On Hold',
            'Pending Materials',
            'Completed',
            'Evaluated',
            'Closed',
            'Cancelled',
        ];

        foreach ($statuses as $index => $status) {
            WorkOrderStatus::query()->firstOrCreate(
                ['name' => $status],
                [
                    'uuid' => (string) Str::uuid(),
                    'sort_order' => $index + 1,
                    'is_terminal' => in_array($status, ['Closed', 'Cancelled'], true),
                    'description' => "{$status} work order status.",
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedAssetCategories(): void
    {
        $this->seedNameRecords(AssetCategory::class, [
            'Air Conditioner',
            'Fire Extinguisher',
            'Smoke Detector',
            'Fire Alarm',
            'Emergency Light',
            'Wall Fan',
            'Orbit Fan',
            'Water Dispenser',
            'Television',
        ]);
    }

    private function seedMaintenanceTypes(): void
    {
        $this->seedNameRecords(MaintenanceType::class, [
            'Cleaning',
            'Inspection',
            'Repair',
            'Calibration',
            'Testing',
            'Replacement',
        ]);
    }

    private function seedInventoryCategories(): void
    {
        $this->seedNameRecords(InventoryCategory::class, [
            'Consumables',
            'Tools',
            'Equipment',
        ]);
    }

    /**
     * @param  class-string  $modelClass
     * @param  array<int, string>  $names
     */
    private function seedNameRecords(string $modelClass, array $names): void
    {
        foreach ($names as $name) {
            $modelClass::query()->firstOrCreate(
                ['name' => $name],
                [
                    'uuid' => (string) Str::uuid(),
                    'description' => "{$name} master record.",
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @param  class-string  $modelClass
     * @param  array<int, array{code: string, name: string}>  $records
     */
    private function seedCodeNameRecords(string $modelClass, array $records): void
    {
        foreach ($records as $record) {
            $modelClass::query()->firstOrCreate(
                ['code' => $record['code']],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $record['name'],
                    'description' => "{$record['name']} master record.",
                    'is_active' => true,
                ],
            );
        }
    }
}
