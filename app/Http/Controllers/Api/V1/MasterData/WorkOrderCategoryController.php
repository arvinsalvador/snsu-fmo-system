<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Requests\Api\V1\MasterData\StoreNameRequest;
use App\Http\Requests\Api\V1\MasterData\UpdateNameRequest;
use App\Models\WorkOrderCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkOrderCategoryController extends MasterDataController
{
    public function modelClass(): string
    {
        return WorkOrderCategory::class;
    }

    public function routeParameter(): string
    {
        return 'workOrderCategory';
    }

    public function index(Request $request): JsonResponse
    {
        return $this->indexResponse($request, 'Work order categories retrieved successfully.');
    }

    public function store(StoreNameRequest $request): JsonResponse
    {
        return $this->storeResponse($request, 'Work order category created successfully.', 'work_order_category');
    }

    public function show(WorkOrderCategory $workOrderCategory): JsonResponse
    {
        return $this->showResponse($workOrderCategory, 'Work order category retrieved successfully.', 'work_order_category');
    }

    public function update(UpdateNameRequest $request, WorkOrderCategory $workOrderCategory): JsonResponse
    {
        return $this->updateResponse($request, $workOrderCategory, 'Work order category updated successfully.', 'work_order_category');
    }
}
