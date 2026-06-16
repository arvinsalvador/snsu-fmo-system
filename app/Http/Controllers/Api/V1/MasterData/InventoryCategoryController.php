<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Requests\Api\V1\MasterData\StoreNameRequest;
use App\Http\Requests\Api\V1\MasterData\UpdateNameRequest;
use App\Models\InventoryCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryCategoryController extends MasterDataController
{
    public function modelClass(): string
    {
        return InventoryCategory::class;
    }

    public function routeParameter(): string
    {
        return 'inventoryCategory';
    }

    public function index(Request $request): JsonResponse
    {
        return $this->indexResponse($request, 'Inventory categories retrieved successfully.');
    }

    public function store(StoreNameRequest $request): JsonResponse
    {
        return $this->storeResponse($request, 'Inventory category created successfully.', 'inventory_category');
    }

    public function show(InventoryCategory $inventoryCategory): JsonResponse
    {
        return $this->showResponse($inventoryCategory, 'Inventory category retrieved successfully.', 'inventory_category');
    }

    public function update(UpdateNameRequest $request, InventoryCategory $inventoryCategory): JsonResponse
    {
        return $this->updateResponse($request, $inventoryCategory, 'Inventory category updated successfully.', 'inventory_category');
    }
}
