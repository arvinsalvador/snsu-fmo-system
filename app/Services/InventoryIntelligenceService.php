<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class InventoryIntelligenceService
{
    public function dashboard(array $filters = []): array
    {
        $from = $this->from($filters);
        $to = $this->to($filters);
        $items = InventoryItem::query();

        return [
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'totals' => [
                'items' => (clone $items)->count(),
                'active_items' => (clone $items)->where('status', 'active')->count(),
                'low_stock_items' => $this->lowStockQuery()->count(),
                'out_of_stock_items' => $this->outOfStockQuery()->count(),
                'inventory_units' => (float) (clone $items)->sum('current_stock'),
            ],
            'health' => $this->healthIndicators(),
            'fast_moving' => $this->movementRanking($filters, 'desc', 5),
            'slow_moving' => $this->movementRanking($filters, 'asc', 5),
            'monthly_consumption' => $this->monthlyConsumption($filters, 12),
        ];
    }

    public function lowStock(array $filters = []): LengthAwarePaginator
    {
        return $this->applyItemFilters($this->lowStockQuery()->with('category'), $filters)
            ->orderBy('current_stock')
            ->orderBy('name')
            ->paginate($this->perPage($filters))
            ->withQueryString();
    }

    public function outOfStock(array $filters = []): LengthAwarePaginator
    {
        return $this->applyItemFilters($this->outOfStockQuery()->with('category'), $filters)
            ->orderBy('name')
            ->paginate($this->perPage($filters))
            ->withQueryString();
    }

    public function fastMoving(array $filters = []): LengthAwarePaginator
    {
        return $this->movementReport($filters, 'desc');
    }

    public function slowMoving(array $filters = []): LengthAwarePaginator
    {
        return $this->movementReport($filters, 'asc');
    }

    public function monthlyConsumption(array $filters = [], int $limit = 24): Collection
    {
        $from = $this->from($filters);
        $to = $this->to($filters);

        return StockMovement::query()
            ->selectRaw("DATE_FORMAT(occurred_at, '%Y-%m') as month")
            ->selectRaw('SUM(ABS(quantity)) as consumed_quantity')
            ->where('quantity', '<', 0)
            ->whereBetween('occurred_at', [$from, $to])
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit($limit)
            ->get()
            ->sortBy('month')
            ->values();
    }

    public function reportRecords(string $report, array $filters = []): Collection
    {
        return match ($report) {
            'low-stock' => $this->applyItemFilters($this->lowStockQuery()->with('category'), $filters)->orderBy('name')->get(),
            'out-of-stock' => $this->applyItemFilters($this->outOfStockQuery()->with('category'), $filters)->orderBy('name')->get(),
            'fast-moving' => $this->movementRecords($filters, 'desc'),
            'slow-moving' => $this->movementRecords($filters, 'asc'),
            'monthly-consumption' => $this->monthlyConsumption($filters, 60),
            default => collect(),
        };
    }

    private function healthIndicators(): array
    {
        $total = max(InventoryItem::query()->count(), 1);
        $low = $this->lowStockQuery()->count();
        $out = $this->outOfStockQuery()->count();
        $healthy = max($total - $low, 0);

        return [
            'healthy_percent' => round(($healthy / $total) * 100, 2),
            'low_stock_percent' => round(($low / $total) * 100, 2),
            'out_of_stock_percent' => round(($out / $total) * 100, 2),
            'status' => $out > 0 ? 'critical' : ($low > 0 ? 'watch' : 'healthy'),
        ];
    }

    private function movementRanking(array $filters, string $direction, int $limit): Collection
    {
        return $this->movementBaseQuery($filters)
            ->orderBy('consumed_quantity', $direction)
            ->orderBy('items.name')
            ->limit($limit)
            ->get();
    }

    private function movementReport(array $filters, string $direction): LengthAwarePaginator
    {
        return $this->movementBaseQuery($filters)
            ->orderBy('consumed_quantity', $direction)
            ->orderBy('items.name')
            ->paginate($this->perPage($filters))
            ->withQueryString();
    }

    private function movementRecords(array $filters, string $direction): Collection
    {
        return $this->movementBaseQuery($filters)
            ->orderBy('consumed_quantity', $direction)
            ->orderBy('items.name')
            ->get();
    }

    private function movementBaseQuery(array $filters): Builder
    {
        return InventoryItem::query()
            ->withoutGlobalScopes()
            ->from('inventory_items as items')
            ->leftJoin('inventory_categories as categories', 'categories.id', '=', 'items.category_id')
            ->leftJoin('stock_movements as movements', function ($join) use ($filters): void {
                $join->on('movements.inventory_item_id', '=', 'items.id')
                    ->where('movements.quantity', '<', 0)
                    ->whereBetween('movements.occurred_at', [$this->from($filters), $this->to($filters)]);
            })
            ->select([
                'items.id',
                'items.uuid',
                'items.item_code',
                'items.name',
                'items.brand',
                'items.unit',
                'items.minimum_stock',
                'items.current_stock',
                'items.status',
                'categories.name as category_name',
            ])
            ->selectRaw('COALESCE(SUM(ABS(movements.quantity)), 0) as consumed_quantity')
            ->selectRaw('COUNT(movements.id) as movement_count')
            ->whereNull('items.deleted_at')
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query->where('items.item_code', 'like', "%{$search}%")->orWhere('items.name', 'like', "%{$search}%")->orWhere('items.brand', 'like', "%{$search}%")))
            ->when($filters['category_id'] ?? null, fn (Builder $query, int|string $categoryId) => $query->where('items.category_id', $categoryId))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('items.status', $status))
            ->groupBy('items.id', 'items.uuid', 'items.item_code', 'items.name', 'items.brand', 'items.unit', 'items.minimum_stock', 'items.current_stock', 'items.status', 'categories.name');
    }

    private function lowStockQuery(): Builder
    {
        return InventoryItem::query()->where('current_stock', '>', 0)->whereColumn('current_stock', '<=', 'minimum_stock');
    }

    private function outOfStockQuery(): Builder
    {
        return InventoryItem::query()->where('current_stock', '<=', 0);
    }

    private function applyItemFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query->where('item_code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")->orWhere('brand', 'like', "%{$search}%")))
            ->when($filters['category_id'] ?? null, fn (Builder $query, int|string $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status));
    }

    private function from(array $filters)
    {
        return isset($filters['from']) ? Carbon::parse($filters['from'])->startOfDay() : now()->subMonths(6)->startOfMonth();
    }

    private function to(array $filters)
    {
        return isset($filters['to']) ? Carbon::parse($filters['to'])->endOfDay() : now()->endOfDay();
    }

    private function perPage(array $filters): int
    {
        return min(max((int) ($filters['per_page'] ?? 15), 10), 100);
    }
}
