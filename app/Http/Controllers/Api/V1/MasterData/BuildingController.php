<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Requests\Api\V1\MasterData\StoreBuildingRequest;
use App\Http\Requests\Api\V1\MasterData\UpdateBuildingRequest;
use App\Http\Resources\Api\V1\BuildingResource;
use App\Models\Building;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BuildingController extends MasterDataController
{
    public function modelClass(): string
    {
        return Building::class;
    }

    public function routeParameter(): string
    {
        return 'building';
    }

    public function index(Request $request): JsonResponse
    {
        return $this->indexResponse($request, 'Buildings retrieved successfully.');
    }

    public function store(StoreBuildingRequest $request): JsonResponse
    {
        return $this->storeResponse($request, 'Building created successfully.', 'building');
    }

    public function show(Building $building): JsonResponse
    {
        return $this->showResponse($building, 'Building retrieved successfully.', 'building', ['floors']);
    }

    public function update(UpdateBuildingRequest $request, Building $building): JsonResponse
    {
        return $this->updateResponse($request, $building, 'Building updated successfully.', 'building');
    }

    protected function resourceClass(): string
    {
        return BuildingResource::class;
    }
}
