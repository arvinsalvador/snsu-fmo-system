<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Requests\Api\V1\MasterData\StoreWorkOrderStatusRequest;
use App\Http\Requests\Api\V1\MasterData\UpdateWorkOrderStatusRequest;
use App\Http\Resources\Api\V1\WorkOrderStatusResource;
use App\Models\WorkOrderStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkOrderStatusController extends MasterDataController
{
    public function modelClass(): string
    {
        return WorkOrderStatus::class;
    }

    public function routeParameter(): string
    {
        return 'workOrderStatus';
    }

    public function index(Request $request): JsonResponse
    {
        return $this->indexResponse($request, 'Work order statuses retrieved successfully.');
    }

    public function store(StoreWorkOrderStatusRequest $request): JsonResponse
    {
        return $this->storeResponse($request, 'Work order status created successfully.', 'work_order_status');
    }

    public function show(WorkOrderStatus $workOrderStatus): JsonResponse
    {
        return $this->showResponse($workOrderStatus, 'Work order status retrieved successfully.', 'work_order_status');
    }

    public function update(UpdateWorkOrderStatusRequest $request, WorkOrderStatus $workOrderStatus): JsonResponse
    {
        return $this->updateResponse($request, $workOrderStatus, 'Work order status updated successfully.', 'work_order_status');
    }

    protected function resourceClass(): string
    {
        return WorkOrderStatusResource::class;
    }
}
