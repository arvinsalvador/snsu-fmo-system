<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Inventory\AdjustInventoryItemRequest;
use App\Http\Requests\Api\V1\Inventory\StockInInventoryItemRequest;
use App\Http\Requests\Api\V1\Inventory\StoreInventoryItemRequest;
use App\Http\Requests\Api\V1\Inventory\UpdateInventoryItemRequest;
use App\Http\Resources\Api\V1\InventoryItemResource;
use App\Http\Resources\Api\V1\StockMovementResource;
use App\Models\InventoryItem;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InventoryItemController extends Controller
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', InventoryItem::class);
        $items = $this->inventory->paginate($request->only(['search', 'category_id', 'status', 'stock', 'sort', 'direction', 'per_page']));

        return response()->json(['success' => true, 'message' => 'Inventory items retrieved successfully.', 'data' => InventoryItemResource::collection($items), 'meta' => ['current_page' => $items->currentPage(), 'per_page' => $items->perPage(), 'total' => $items->total(), 'last_page' => $items->lastPage()]]);
    }

    public function store(StoreInventoryItemRequest $request): JsonResponse
    {
        Gate::authorize('create', InventoryItem::class);
        $item = $this->inventory->create($request->validated(), $request->user());

        return response()->json(['success' => true, 'message' => 'Inventory item created successfully.', 'data' => ['item' => new InventoryItemResource($item)]], 201);
    }

    public function show(InventoryItem $inventoryItem): JsonResponse
    {
        Gate::authorize('view', $inventoryItem);

        return response()->json(['success' => true, 'message' => 'Inventory item retrieved successfully.', 'data' => ['item' => new InventoryItemResource($inventoryItem->load('category'))]]);
    }

    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventoryItem): JsonResponse
    {
        Gate::authorize('update', $inventoryItem);
        $item = $this->inventory->update($inventoryItem, $request->validated());

        return response()->json(['success' => true, 'message' => 'Inventory item updated successfully.', 'data' => ['item' => new InventoryItemResource($item)]]);
    }

    public function stockIn(StockInInventoryItemRequest $request, InventoryItem $inventoryItem): JsonResponse
    {
        Gate::authorize('adjust', $inventoryItem);
        $item = $this->inventory->stockIn($inventoryItem, $request->validated(), $request->user());

        return response()->json(['success' => true, 'message' => 'Stock received successfully.', 'data' => ['item' => new InventoryItemResource($item)]]);
    }

    public function adjust(AdjustInventoryItemRequest $request, InventoryItem $inventoryItem): JsonResponse
    {
        Gate::authorize('adjust', $inventoryItem);
        $item = $this->inventory->adjust($inventoryItem, $request->validated(), $request->user());

        return response()->json(['success' => true, 'message' => 'Stock adjusted successfully.', 'data' => ['item' => new InventoryItemResource($item)]]);
    }

    public function movements(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        Gate::authorize('view', $inventoryItem);
        $movements = $this->inventory->movements($inventoryItem, $request->only(['type', 'per_page']));

        return response()->json(['success' => true, 'message' => 'Stock movements retrieved successfully.', 'data' => StockMovementResource::collection($movements), 'meta' => ['current_page' => $movements->currentPage(), 'per_page' => $movements->perPage(), 'total' => $movements->total(), 'last_page' => $movements->lastPage()]]);
    }
}
