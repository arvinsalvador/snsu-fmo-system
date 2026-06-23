<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($this->perPage($filters))->withQueryString();
    }

    public function records(array $filters = []): Collection
    {
        return $this->query($filters)->get();
    }

    public function create(array $data, ?User $actor = null): InventoryItem
    {
        $initialStock = (float) ($data['current_stock'] ?? 0);
        unset($data['current_stock']);

        return DB::transaction(function () use ($data, $initialStock, $actor): InventoryItem {
            $item = InventoryItem::query()->create([...$data, 'current_stock' => 0]);
            if ($initialStock > 0) {
                $item = $this->changeStock($item, $initialStock, 'stock_in', $actor, 'Initial stock balance.');
            }

            return $item->load('category');
        });
    }

    public function update(InventoryItem $item, array $data): InventoryItem
    {
        unset($data['current_stock']);
        $item->update($data);

        return $item->refresh()->load('category');
    }

    public function stockIn(InventoryItem $item, array $data, User $actor): InventoryItem
    {
        return $this->changeStock($item, (float) $data['quantity'], 'stock_in', $actor, $data['remarks'] ?? null);
    }

    public function adjust(InventoryItem $item, array $data, User $actor): InventoryItem
    {
        $quantity = (float) $data['quantity'];
        if (($data['direction'] ?? null) === 'decrease' && $quantity > 0) {
            $quantity *= -1;
        }

        return $this->changeStock($item, $quantity, 'adjustment', $actor, $data['remarks'] ?? null);
    }

    public function movements(InventoryItem $item, array $filters = []): LengthAwarePaginator
    {
        return $item->stockMovements()->with('creator')->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('movement_type', $type))->paginate($this->perPage($filters))->withQueryString();
    }

    private function changeStock(InventoryItem $item, float $quantity, string $type, ?User $actor, ?string $remarks): InventoryItem
    {
        return DB::transaction(function () use ($item, $quantity, $type, $actor, $remarks): InventoryItem {
            $locked = InventoryItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $before = (float) $locked->current_stock;
            $after = $before + $quantity;
            if ($type === 'stock_in' && $quantity <= 0) {
                throw ValidationException::withMessages(['quantity' => 'Stock in quantity must be greater than zero.']);
            }
            if ($type === 'adjustment' && $quantity === 0.0) {
                throw ValidationException::withMessages(['quantity' => 'Adjustment quantity cannot be zero.']);
            }
            if ($after < 0) {
                throw ValidationException::withMessages(['quantity' => 'Inventory stock cannot become negative.']);
            }
            $locked->forceFill(['current_stock' => $after])->save();
            StockMovement::query()->create(['inventory_item_id' => $locked->id, 'created_by' => $actor?->id, 'movement_type' => $type, 'quantity' => $quantity, 'stock_before' => $before, 'stock_after' => $after, 'remarks' => $remarks, 'occurred_at' => now()]);

            return $locked->refresh()->load('category');
        });
    }

    private function query(array $filters): Builder
    {
        $sort = in_array($filters['sort'] ?? '', ['item_code', 'name', 'current_stock', 'minimum_stock', 'status', 'created_at'], true) ? $filters['sort'] : 'name';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return InventoryItem::query()->with('category')
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query->where('item_code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")->orWhere('brand', 'like', "%{$search}%")->orWhere('unit', 'like', "%{$search}%")))
            ->when($filters['category_id'] ?? null, fn (Builder $query, int|string $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when(($filters['stock'] ?? null) === 'low', fn (Builder $query) => $query->whereColumn('current_stock', '<=', 'minimum_stock'))
            ->orderBy($sort, $direction);
    }

    private function perPage(array $filters): int
    {
        return min(max((int) ($filters['per_page'] ?? 15), 10), 100);
    }
}
