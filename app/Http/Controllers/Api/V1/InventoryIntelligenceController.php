<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Services\InventoryIntelligenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InventoryIntelligenceController extends Controller
{
    public function __construct(private readonly InventoryIntelligenceService $intelligence) {}

    public function dashboard(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', InventoryItem::class);

        return response()->json([
            'success' => true,
            'message' => 'Inventory intelligence dashboard retrieved successfully.',
            'data' => $this->intelligence->dashboard($request->only(['from', 'to'])),
        ]);
    }

    public function lowStock(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', InventoryItem::class);
        $items = $this->intelligence->lowStock($request->only(['search', 'category_id', 'status', 'per_page']));

        return $this->paginated('Low stock items retrieved successfully.', $items);
    }

    public function outOfStock(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', InventoryItem::class);
        $items = $this->intelligence->outOfStock($request->only(['search', 'category_id', 'status', 'per_page']));

        return $this->paginated('Out of stock items retrieved successfully.', $items);
    }

    public function fastMoving(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', InventoryItem::class);
        $items = $this->intelligence->fastMoving($request->only(['search', 'category_id', 'status', 'from', 'to', 'per_page']));

        return $this->paginated('Fast moving materials retrieved successfully.', $items);
    }

    public function slowMoving(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', InventoryItem::class);
        $items = $this->intelligence->slowMoving($request->only(['search', 'category_id', 'status', 'from', 'to', 'per_page']));

        return $this->paginated('Slow moving materials retrieved successfully.', $items);
    }

    public function monthlyConsumption(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', InventoryItem::class);

        return response()->json([
            'success' => true,
            'message' => 'Monthly consumption summary retrieved successfully.',
            'data' => $this->intelligence->monthlyConsumption($request->only(['from', 'to']))->values(),
        ]);
    }

    private function paginated(string $message, $paginator): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
