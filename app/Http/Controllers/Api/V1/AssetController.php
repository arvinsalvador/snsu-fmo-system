<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Assets\AssetIndexRequest;
use App\Http\Requests\Api\V1\Assets\StoreAssetPhotoRequest;
use App\Http\Requests\Api\V1\Assets\StoreAssetRequest;
use App\Http\Requests\Api\V1\Assets\UpdateAssetRequest;
use App\Http\Resources\Api\V1\AssetPhotoResource;
use App\Http\Resources\Api\V1\AssetResource;
use App\Models\Asset;
use App\Services\AssetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AssetController extends Controller
{
    public function __construct(private readonly AssetService $assets) {}

    public function index(AssetIndexRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', Asset::class);
        $assets = $this->assets->paginate($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Assets retrieved successfully.',
            'data' => AssetResource::collection($assets),
            'meta' => [
                'current_page' => $assets->currentPage(),
                'per_page' => $assets->perPage(),
                'total' => $assets->total(),
                'last_page' => $assets->lastPage(),
            ],
            'links' => ['first' => $assets->url(1), 'last' => $assets->url($assets->lastPage()), 'prev' => $assets->previousPageUrl(), 'next' => $assets->nextPageUrl()],
        ]);
    }

    public function lookup(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Asset::class);

        return response()->json([
            'success' => true,
            'message' => 'Asset lookup retrieved successfully.',
            'data' => AssetResource::collection($this->assets->lookup(
                (string) $request->query('search', ''),
                $request->integer('limit', 50),
            )),
        ]);
    }

    public function store(StoreAssetRequest $request): JsonResponse
    {
        Gate::authorize('create', Asset::class);
        $asset = $this->assets->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Asset created successfully.',
            'data' => ['asset' => new AssetResource($asset)],
        ], 201);
    }

    public function show(Asset $asset): JsonResponse
    {
        Gate::authorize('view', $asset);

        return response()->json([
            'success' => true,
            'message' => 'Asset retrieved successfully.',
            'data' => ['asset' => new AssetResource($asset->load($this->assets->relations()))],
        ]);
    }

    public function update(UpdateAssetRequest $request, Asset $asset): JsonResponse
    {
        Gate::authorize('update', $asset);
        $asset = $this->assets->update($asset, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Asset updated successfully.',
            'data' => ['asset' => new AssetResource($asset)],
        ]);
    }

    public function destroy(Asset $asset): JsonResponse
    {
        Gate::authorize('delete', $asset);
        $this->assets->delete($asset);

        return response()->json([
            'success' => true,
            'message' => 'Asset archived successfully.',
        ]);
    }

    public function photos(StoreAssetPhotoRequest $request, Asset $asset): JsonResponse
    {
        Gate::authorize('update', $asset);
        $photo = $this->assets->addPhoto($asset, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Asset photo metadata added successfully.',
            'data' => ['photo' => new AssetPhotoResource($photo->load('uploader'))],
        ], 201);
    }
}
