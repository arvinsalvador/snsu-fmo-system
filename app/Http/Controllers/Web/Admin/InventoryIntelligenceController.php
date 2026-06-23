<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Services\AdminWebService;
use App\Services\InventoryIntelligenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryIntelligenceController extends Controller
{
    public function __construct(
        private readonly InventoryIntelligenceService $intelligence,
        private readonly AdminWebService $web,
    ) {}

    public function dashboard(Request $request): View
    {
        Gate::authorize('viewAny', InventoryItem::class);
        $filters = $request->only(['from', 'to']);

        return view('admin.inventory-intelligence.dashboard', [
            'dashboard' => $this->intelligence->dashboard($filters),
            'filters' => $filters,
        ]);
    }

    public function report(Request $request, string $report): View
    {
        Gate::authorize('viewAny', InventoryItem::class);
        $filters = $request->only(['search', 'category_id', 'status', 'from', 'to', 'per_page']);

        return view('admin.inventory-intelligence.report', [
            'report' => $report,
            'title' => $this->title($report),
            'records' => $this->records($report, $filters),
            'categories' => InventoryCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    public function export(Request $request, string $report): StreamedResponse
    {
        Gate::authorize('viewAny', InventoryItem::class);
        $filters = $request->only(['search', 'category_id', 'status', 'from', 'to']);
        $records = $this->intelligence->reportRecords($report, $filters);

        return $this->web->csv('inventory-'.$report, $this->headers($report), $records, fn ($record): array => $this->row($report, $record));
    }

    private function records(string $report, array $filters)
    {
        return match ($report) {
            'low-stock' => $this->intelligence->lowStock($filters),
            'out-of-stock' => $this->intelligence->outOfStock($filters),
            'fast-moving' => $this->intelligence->fastMoving($filters),
            'slow-moving' => $this->intelligence->slowMoving($filters),
            'monthly-consumption' => $this->intelligence->monthlyConsumption($filters),
            default => abort(404),
        };
    }

    private function title(string $report): string
    {
        return match ($report) {
            'low-stock' => 'Low stock monitoring',
            'out-of-stock' => 'Out of stock monitoring',
            'fast-moving' => 'Fast moving materials',
            'slow-moving' => 'Slow moving materials',
            'monthly-consumption' => 'Monthly consumption summary',
            default => abort(404),
        };
    }

    private function headers(string $report): array
    {
        return $report === 'monthly-consumption'
            ? ['Month', 'Consumed Quantity']
            : ['Item Code', 'Category', 'Name', 'Brand', 'Unit', 'Minimum Stock', 'Current Stock', 'Consumed Quantity', 'Movement Count', 'Status'];
    }

    private function row(string $report, $record): array
    {
        if ($report === 'monthly-consumption') {
            return [$record->month, $record->consumed_quantity];
        }

        return [
            $record->item_code,
            $record->category?->name ?? $record->category_name,
            $record->name,
            $record->brand,
            $record->unit,
            $record->minimum_stock,
            $record->current_stock,
            $record->consumed_quantity ?? '',
            $record->movement_count ?? '',
            $record->status,
        ];
    }
}
