<?php

namespace App\Services;

use App\Models\Building;
use App\Models\Department;
use App\Models\Floor;
use App\Models\Priority;
use App\Models\Room;
use App\Models\WorkOrderCategory;
use App\Models\WorkOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class MasterDataWebService
{
    /** @return array<string, array<string, mixed>> */
    public function modules(): array
    {
        return [
            'buildings' => $this->makeModule('Buildings', Building::class, 'building', ['code' => 'Code', 'name' => 'Name'], [
                $this->field('code', 'Code'), $this->field('name', 'Name'), $this->field('description', 'Description', 'textarea'),
            ]),
            'floors' => $this->makeModule('Floors', Floor::class, 'floor', ['building.name' => 'Building', 'floor_name' => 'Floor'], [
                $this->field('building_id', 'Building', 'select', 'buildings'), $this->field('floor_name', 'Floor name'), $this->field('description', 'Description', 'textarea'),
            ], ['building']),
            'rooms' => $this->makeModule('Rooms', Room::class, 'room', ['room_code' => 'Code', 'room_name' => 'Room', 'floor.floor_name' => 'Floor', 'floor.building.name' => 'Building'], [
                $this->field('floor_id', 'Floor', 'select', 'floors'), $this->field('room_code', 'Room code'), $this->field('room_name', 'Room name'), $this->field('description', 'Description', 'textarea'),
            ], ['floor.building']),
            'departments' => $this->makeModule('Departments', Department::class, 'department', ['code' => 'Code', 'name' => 'Name'], [
                $this->field('code', 'Code'), $this->field('name', 'Name'), $this->field('description', 'Description', 'textarea'),
            ]),
            'work-order-categories' => $this->makeModule('Work Order Categories', WorkOrderCategory::class, 'workOrderCategory', ['name' => 'Name'], [
                $this->field('name', 'Name'), $this->field('description', 'Description', 'textarea'),
            ]),
            'priorities' => $this->makeModule('Priorities', Priority::class, 'priority', ['level' => 'Level', 'name' => 'Name', 'color' => 'Color'], [
                $this->field('name', 'Name'), $this->field('level', 'Level', 'number'), $this->field('color', 'Color', 'color'), $this->field('description', 'Description', 'textarea'),
            ]),
            'work-order-statuses' => $this->makeModule('Work Order Statuses', WorkOrderStatus::class, 'workOrderStatus', ['sort_order' => 'Order', 'name' => 'Name', 'is_terminal' => 'Terminal'], [
                $this->field('name', 'Name'), $this->field('sort_order', 'Sort order', 'number'), $this->field('is_terminal', 'Terminal status', 'checkbox'), $this->field('description', 'Description', 'textarea'),
            ]),
        ];
    }

    /** @return array<string, mixed> */
    public function module(string $key): array
    {
        return $this->modules()[$key] ?? throw new InvalidArgumentException('Unknown master-data module.');
    }

    /** @return array<string, Collection<int, Model>> */
    public function options(): array
    {
        return [
            'buildings' => Building::query()->where('is_active', true)->orderBy('name')->get(),
            'floors' => Floor::query()->with('building')->where('is_active', true)->orderBy('floor_name')->get(),
        ];
    }

    public function displayValue(Model $record, string $key): string
    {
        $value = data_get($record, $key);

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return filled($value) ? (string) $value : '—';
    }

    /** @return array<string, mixed> */
    private function makeModule(string $label, string $model, string $parameter, array $columns, array $fields, array $with = []): array
    {
        return compact('label', 'model', 'parameter', 'columns', 'fields', 'with');
    }

    /** @return array<string, string> */
    private function field(string $name, string $label, string $type = 'text', string $options = ''): array
    {
        return compact('name', 'label', 'type', 'options');
    }
}
