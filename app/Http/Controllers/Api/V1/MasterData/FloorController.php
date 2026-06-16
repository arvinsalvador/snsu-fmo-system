<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Requests\Api\V1\MasterData\StoreFloorRequest;
use App\Http\Requests\Api\V1\MasterData\UpdateFloorRequest;
use App\Http\Resources\Api\V1\FloorResource;
use App\Models\Floor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FloorController extends MasterDataController
{
    public function modelClass(): string
    {
        return Floor::class;
    }

    public function routeParameter(): string
    {
        return 'floor';
    }

    public function index(Request $request): JsonResponse
    {
        return $this->indexResponse($request, 'Floors retrieved successfully.', ['building']);
    }

    public function store(StoreFloorRequest $request): JsonResponse
    {
        return $this->storeResponse($request, 'Floor created successfully.', 'floor');
    }

    public function show(Floor $floor): JsonResponse
    {
        return $this->showResponse($floor, 'Floor retrieved successfully.', 'floor', ['building', 'rooms']);
    }

    public function update(UpdateFloorRequest $request, Floor $floor): JsonResponse
    {
        return $this->updateResponse($request, $floor, 'Floor updated successfully.', 'floor');
    }

    protected function resourceClass(): string
    {
        return FloorResource::class;
    }
}
