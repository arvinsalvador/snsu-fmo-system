<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class MasterDataService
{
    public function paginate(string $modelClass, array $filters = [], array $with = []): LengthAwarePaginator
    {
        return $this->query($modelClass, $filters, $with)
            ->paginate(min(max((int) ($filters['per_page'] ?? 15), 10), 100))
            ->withQueryString();
    }

    public function records(string $modelClass, array $filters = [], array $with = []): Collection
    {
        return $this->query($modelClass, $filters, $with)->get();
    }

    public function create(string $modelClass, array $data): Model
    {
        return $modelClass::query()->create($data);
    }

    public function update(Model $model, array $data): Model
    {
        $model->update($data);

        return $model->refresh();
    }

    public function delete(Model $model): void
    {
        $model->delete();
    }

    private function query(string $modelClass, array $filters, array $with): Builder
    {
        $query = $modelClass::query()->with($with)
            ->when($filters['search'] ?? null, function (Builder $query, string $search) use ($modelClass): void {
                $columns = $this->searchableColumns($modelClass);
                $query->where(function (Builder $query) use ($columns, $search): void {
                    foreach ($columns as $index => $column) {
                        $query->{$index === 0 ? 'where' : 'orWhere'}($column, 'like', "%{$search}%");
                    }
                });
            })
            ->when(($filters['status'] ?? null) === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn (Builder $query) => $query->where('is_active', false));

        $sort = (string) ($filters['sort'] ?? '');
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        if (in_array($sort, $this->sortableColumns($modelClass), true)) {
            return $query->orderBy($sort, $direction);
        }

        return $query->orderByRaw($this->orderExpression($modelClass));
    }

    private function orderExpression(string $modelClass): string
    {
        $instance = new $modelClass;
        foreach (['sort_order', 'level', 'floor_name', 'room_name', 'name'] as $column) {
            if ($instance->getConnection()->getSchemaBuilder()->hasColumn($instance->getTable(), $column)) {
                return "{$column} asc";
            }
        }

        return 'id asc';
    }

    private function searchableColumns(string $modelClass): array
    {
        return $this->existingColumns($modelClass, ['name', 'code', 'floor_name', 'room_name', 'room_code', 'description']);
    }

    private function sortableColumns(string $modelClass): array
    {
        return $this->existingColumns($modelClass, ['name', 'code', 'floor_name', 'room_name', 'room_code', 'level', 'sort_order', 'is_active', 'created_at']);
    }

    private function existingColumns(string $modelClass, array $columns): array
    {
        $instance = new $modelClass;
        $schema = $instance->getConnection()->getSchemaBuilder();

        return array_values(array_filter($columns, fn (string $column): bool => $schema->hasColumn($instance->getTable(), $column)));
    }
}
