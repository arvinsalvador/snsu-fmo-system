<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class MasterDataService
{
    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $with
     */
    public function paginate(string $modelClass, array $filters = [], array $with = []): LengthAwarePaginator
    {
        return $modelClass::query()
            ->with($with)
            ->when($filters['search'] ?? null, function ($query, string $search) use ($modelClass): void {
                $columns = $this->searchableColumns($modelClass);

                $query->where(function ($query) use ($columns, $search): void {
                    foreach ($columns as $index => $column) {
                        $method = $index === 0 ? 'where' : 'orWhere';
                        $query->{$method}($column, 'like', "%{$search}%");
                    }
                });
            })
            ->when(array_key_exists('status', $filters), function ($query) use ($filters): void {
                if ($filters['status'] === 'active') {
                    $query->where('is_active', true);
                }

                if ($filters['status'] === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->orderByRaw($this->orderExpression($modelClass))
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, mixed>  $data
     */
    public function create(string $modelClass, array $data): Model
    {
        return $modelClass::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model
    {
        $model->update($data);

        return $model->refresh();
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function orderExpression(string $modelClass): string
    {
        $instance = new $modelClass;

        if ($instance->getConnection()->getSchemaBuilder()->hasColumn($instance->getTable(), 'sort_order')) {
            return 'sort_order asc';
        }

        if ($instance->getConnection()->getSchemaBuilder()->hasColumn($instance->getTable(), 'level')) {
            return 'level asc';
        }

        if ($instance->getConnection()->getSchemaBuilder()->hasColumn($instance->getTable(), 'floor_name')) {
            return 'floor_name asc';
        }

        if ($instance->getConnection()->getSchemaBuilder()->hasColumn($instance->getTable(), 'room_name')) {
            return 'room_name asc';
        }

        if ($instance->getConnection()->getSchemaBuilder()->hasColumn($instance->getTable(), 'name')) {
            return 'name asc';
        }

        return 'id asc';
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return array<int, string>
     */
    private function searchableColumns(string $modelClass): array
    {
        $instance = new $modelClass;
        $schema = $instance->getConnection()->getSchemaBuilder();

        return array_values(array_filter(
            ['name', 'code', 'floor_name', 'room_name', 'room_code', 'description'],
            fn (string $column): bool => $schema->hasColumn($instance->getTable(), $column),
        ));
    }
}
