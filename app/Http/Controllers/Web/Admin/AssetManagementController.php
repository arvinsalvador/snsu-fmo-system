<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Assets\StoreAssetPhotoRequest;
use App\Http\Requests\Api\V1\Assets\StoreAssetRequest;
use App\Http\Requests\Api\V1\Assets\UpdateAssetRequest;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Room;
use App\Services\AdminWebService;
use App\Services\AssetService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetManagementController extends Controller
{
    public function __construct(
        private readonly AssetService $assets,
        private readonly AdminWebService $web,
    ) {}

    public function create(): View
    {
        Gate::authorize('create', Asset::class);

        return view('admin.assets.form', [
            'asset' => null,
            ...$this->options(),
        ]);
    }

    public function store(StoreAssetRequest $request): RedirectResponse
    {
        Gate::authorize('create', Asset::class);
        $asset = $this->assets->create($request->validated());

        return to_route('admin.assets.show', $asset)->with('success', 'Asset created successfully.');
    }

    public function edit(Asset $asset): View
    {
        Gate::authorize('update', $asset);

        return view('admin.assets.form', [
            'asset' => $asset->load($this->assets->relations()),
            ...$this->options(),
        ]);
    }

    public function update(UpdateAssetRequest $request, Asset $asset): RedirectResponse
    {
        Gate::authorize('update', $asset);
        $this->assets->update($asset, $request->validated());

        return to_route('admin.assets.show', $asset)->with('success', 'Asset updated successfully.');
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        Gate::authorize('delete', $asset);
        $this->assets->delete($asset);

        return to_route('admin.assets.index')->with('success', 'Asset archived successfully.');
    }

    public function storePhoto(StoreAssetPhotoRequest $request, Asset $asset): RedirectResponse
    {
        Gate::authorize('update', $asset);
        $this->assets->addPhoto($asset, $request->validated(), $request->user());

        return to_route('admin.assets.show', $asset)->with('success', 'Asset photo metadata added successfully.');
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('export', Asset::class);
        $records = $this->assets->records($request->only(['search', 'asset_category_id', 'building_id', 'floor_id', 'room_id', 'status', 'sort', 'direction']));

        return $this->web->csv('assets', ['Asset Tag', 'Name', 'Category', 'Building', 'Floor', 'Room', 'Location', 'Brand', 'Model', 'Serial Number', 'Purchase Date', 'Warranty Until', 'Status'], $records, fn (Asset $asset): array => [
            $asset->asset_tag,
            $asset->name,
            $asset->category?->name,
            $asset->building?->name,
            $asset->floor?->floor_name,
            $asset->room?->room_code,
            $asset->location,
            $asset->brand,
            $asset->model,
            $asset->serial_number,
            $asset->purchase_date?->toDateString(),
            $asset->warranty_until?->toDateString(),
            $asset->status,
        ]);
    }

    private function options(): array
    {
        return [
            'categories' => AssetCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'buildings' => Building::query()->where('is_active', true)->orderBy('name')->get(),
            'floors' => Floor::query()->where('is_active', true)->orderBy('floor_number')->get(),
            'rooms' => Room::query()->where('is_active', true)->orderBy('room_code')->get(),
            'statuses' => Asset::STATUSES,
        ];
    }
}
