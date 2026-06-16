<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MasterDataResource;
use App\Services\MasterDataService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

abstract class MasterDataController extends Controller
{
    public function __construct(protected readonly MasterDataService $masterData) {}

    abstract public function modelClass(): string;

    abstract public function routeParameter(): string;

    public function modelTable(): string
    {
        return (new ($this->modelClass()))->getTable();
    }

    protected function indexResponse(Request $request, string $message, array $with = []): JsonResponse
    {
        Gate::authorize('manageMasterData', $this->modelClass());

        $records = $this->masterData->paginate($this->modelClass(), $request->only(['search', 'status', 'per_page']), $with);
        $resourceClass = $this->resourceClass();

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $resourceClass::collection($records),
            'meta' => [
                'current_page' => $records->currentPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
                'last_page' => $records->lastPage(),
            ],
        ]);
    }

    protected function storeResponse(FormRequest $request, string $message, string $dataKey): JsonResponse
    {
        Gate::authorize('manageMasterData', $this->modelClass());

        $record = $this->masterData->create($this->modelClass(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [$dataKey => $this->resource($record)],
        ], 201);
    }

    protected function showResponse(Model $record, string $message, string $dataKey, array $with = []): JsonResponse
    {
        Gate::authorize('view', $record);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [$dataKey => $this->resource($record->load($with))],
        ]);
    }

    protected function updateResponse(FormRequest $request, Model $record, string $message, string $dataKey): JsonResponse
    {
        Gate::authorize('update', $record);

        $record = $this->masterData->update($record, $request->validated());

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [$dataKey => $this->resource($record)],
        ]);
    }

    /**
     * @return class-string<JsonResource>
     */
    protected function resourceClass(): string
    {
        return MasterDataResource::class;
    }

    private function resource(Model $record): JsonResource
    {
        $resourceClass = $this->resourceClass();

        return new $resourceClass($record);
    }
}
