<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Requests\Api\V1\MasterData\StoreNameRequest;
use App\Http\Requests\Api\V1\MasterData\UpdateNameRequest;
use App\Models\MaintenanceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceTypeController extends MasterDataController
{
    public function modelClass(): string
    {
        return MaintenanceType::class;
    }

    public function routeParameter(): string
    {
        return 'maintenanceType';
    }

    public function index(Request $request): JsonResponse
    {
        return $this->indexResponse($request, 'Maintenance types retrieved successfully.');
    }

    public function store(StoreNameRequest $request): JsonResponse
    {
        return $this->storeResponse($request, 'Maintenance type created successfully.', 'maintenance_type');
    }

    public function show(MaintenanceType $maintenanceType): JsonResponse
    {
        return $this->showResponse($maintenanceType, 'Maintenance type retrieved successfully.', 'maintenance_type');
    }

    public function update(UpdateNameRequest $request, MaintenanceType $maintenanceType): JsonResponse
    {
        return $this->updateResponse($request, $maintenanceType, 'Maintenance type updated successfully.', 'maintenance_type');
    }
}
