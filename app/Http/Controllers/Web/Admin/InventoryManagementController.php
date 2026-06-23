<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Inventory\AdjustInventoryItemRequest;
use App\Http\Requests\Api\V1\Inventory\StockInInventoryItemRequest;
use App\Http\Requests\Api\V1\Inventory\StoreInventoryItemRequest;
use App\Http\Requests\Api\V1\Inventory\UpdateInventoryItemRequest;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Services\AdminWebService;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryManagementController extends Controller
{
    public function __construct(private readonly InventoryService $inventory, private readonly AdminWebService $web) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', InventoryItem::class);
        $filters = $request->only(['search', 'category_id', 'status', 'stock', 'sort', 'direction', 'per_page']);

        return view('admin.inventory.index', ['items' => $this->inventory->paginate($filters), 'categories' => $this->categories(), 'filters' => $filters]);
    }

    public function create(): View
    {
        Gate::authorize('create', InventoryItem::class);

        return view('admin.inventory.form', ['item' => null, 'categories' => $this->categories()]);
    }

    public function store(StoreInventoryItemRequest $request): RedirectResponse
    {
        Gate::authorize('create', InventoryItem::class);
        $item = $this->inventory->create($request->validated(), $request->user());

        return redirect()->route('admin.inventory.show', $item)->with('success', 'Inventory item created successfully.');
    }

    public function show(Request $request, InventoryItem $inventoryItem): View
    {
        Gate::authorize('view', $inventoryItem);

        return view('admin.inventory.show', ['item' => $inventoryItem->load('category'), 'movements' => $this->inventory->movements($inventoryItem, $request->only(['type', 'per_page']))]);
    }

    public function edit(InventoryItem $inventoryItem): View
    {
        Gate::authorize('update', $inventoryItem);

        return view('admin.inventory.form', ['item' => $inventoryItem->load('category'), 'categories' => $this->categories()]);
    }

    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventoryItem): RedirectResponse
    {
        Gate::authorize('update', $inventoryItem);
        $this->inventory->update($inventoryItem, $request->validated());

        return redirect()->route('admin.inventory.show', $inventoryItem)->with('success', 'Inventory item updated successfully.');
    }

    public function stockIn(StockInInventoryItemRequest $request, InventoryItem $inventoryItem): RedirectResponse
    {
        Gate::authorize('adjust', $inventoryItem);
        $this->inventory->stockIn($inventoryItem, $request->validated(), $request->user());

        return redirect()->route('admin.inventory.show', $inventoryItem)->with('success', 'Stock received successfully.');
    }

    public function adjust(AdjustInventoryItemRequest $request, InventoryItem $inventoryItem): RedirectResponse
    {
        Gate::authorize('adjust', $inventoryItem);
        $this->inventory->adjust($inventoryItem, $request->validated(), $request->user());

        return redirect()->route('admin.inventory.show', $inventoryItem)->with('success', 'Stock adjusted successfully.');
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('export', InventoryItem::class);
        $records = $this->inventory->records($request->only(['search', 'category_id', 'status', 'stock', 'sort', 'direction']));

        return $this->web->csv('inventory-items', ['Item Code', 'Category', 'Name', 'Brand', 'Unit', 'Minimum Stock', 'Current Stock', 'Low Stock', 'Status', 'Remarks'], $records, fn (InventoryItem $item): array => [$item->item_code, $item->category?->name, $item->name, $item->brand, $item->unit, $item->minimum_stock, $item->current_stock, $item->is_low_stock ? 'Yes' : 'No', $item->status, $item->remarks]);
    }

    private function categories()
    {
        return InventoryCategory::query()->where('is_active', true)->orderBy('name')->get();
    }
}
