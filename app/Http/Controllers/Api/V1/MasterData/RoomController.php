<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Requests\Api\V1\MasterData\StoreRoomRequest;
use App\Http\Requests\Api\V1\MasterData\UpdateRoomRequest;
use App\Http\Resources\Api\V1\RoomResource;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends MasterDataController
{
    public function modelClass(): string
    {
        return Room::class;
    }

    public function routeParameter(): string
    {
        return 'room';
    }

    public function index(Request $request): JsonResponse
    {
        return $this->indexResponse($request, 'Rooms retrieved successfully.', ['floor.building']);
    }

    public function store(StoreRoomRequest $request): JsonResponse
    {
        return $this->storeResponse($request, 'Room created successfully.', 'room');
    }

    public function show(Room $room): JsonResponse
    {
        return $this->showResponse($room, 'Room retrieved successfully.', 'room', ['floor.building']);
    }

    public function update(UpdateRoomRequest $request, Room $room): JsonResponse
    {
        return $this->updateResponse($request, $room, 'Room updated successfully.', 'room');
    }

    protected function resourceClass(): string
    {
        return RoomResource::class;
    }
}
