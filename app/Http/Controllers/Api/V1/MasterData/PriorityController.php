<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Requests\Api\V1\MasterData\StorePriorityRequest;
use App\Http\Requests\Api\V1\MasterData\UpdatePriorityRequest;
use App\Http\Resources\Api\V1\PriorityResource;
use App\Models\Priority;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PriorityController extends MasterDataController
{
    public function modelClass(): string
    {
        return Priority::class;
    }

    public function routeParameter(): string
    {
        return 'priority';
    }

    public function index(Request $request): JsonResponse
    {
        return $this->indexResponse($request, 'Priorities retrieved successfully.');
    }

    public function store(StorePriorityRequest $request): JsonResponse
    {
        return $this->storeResponse($request, 'Priority created successfully.', 'priority');
    }

    public function show(Priority $priority): JsonResponse
    {
        return $this->showResponse($priority, 'Priority retrieved successfully.', 'priority');
    }

    public function update(UpdatePriorityRequest $request, Priority $priority): JsonResponse
    {
        return $this->updateResponse($request, $priority, 'Priority updated successfully.', 'priority');
    }

    protected function resourceClass(): string
    {
        return PriorityResource::class;
    }
}
