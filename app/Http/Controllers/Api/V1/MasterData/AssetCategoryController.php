<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Requests\Api\V1\MasterData\StoreNameRequest;
use App\Http\Requests\Api\V1\MasterData\UpdateNameRequest;
use App\Models\AssetCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetCategoryController extends MasterDataController
{
    public function modelClass(): string
    {
        return AssetCategory::class;
    }

    public function routeParameter(): string
    {
        return 'assetCategory';
    }

    public function index(Request $request): JsonResponse
    {
        return $this->indexResponse($request, 'Asset categories retrieved successfully.');
    }

    public function store(StoreNameRequest $request): JsonResponse
    {
        return $this->storeResponse($request, 'Asset category created successfully.', 'asset_category');
    }

    public function show(AssetCategory $assetCategory): JsonResponse
    {
        return $this->showResponse($assetCategory, 'Asset category retrieved successfully.', 'asset_category');
    }

    public function update(UpdateNameRequest $request, AssetCategory $assetCategory): JsonResponse
    {
        return $this->updateResponse($request, $assetCategory, 'Asset category updated successfully.', 'asset_category');
    }
}
