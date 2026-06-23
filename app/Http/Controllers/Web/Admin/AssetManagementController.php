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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetManagementController extends Controller
{
    public function __construct(private readonly AssetService $assets, private readonly AdminWebService $web) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Asset::class);
        $filters = $request->only(['search', 'asset_category_id', 'building_id', 'floor_id', 'room_id', 'status', 'sort', 'direction', 'per_page']);

        return view('admin.assets.index', [
            'assets' => $this->assets->paginate($filters),
            'filters' => $filters,
            ...$this->formOptions(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Asset::class);

        return view('admin.assets.form', ['asset' => null, ...$this->formOptions()]);
    }

    public function store(StoreAssetRequest $request): RedirectResponse
    {
        Gate::authorize('create', Asset::class);
        $asset = $this->assets->create($request->validated());

        return redirect()->route('admin.assets.show', $asset)->with('success', 'Asset created successfully.');
    }

    public function show(Request $request, Asset $asset): View
    {
        Gate::authorize('view', $asset);

        return view('admin.assets.show', [
            'asset' => $asset->load($this->assets->relations()),
            'photos' => $asset->photos()->with('uploader')->paginate((int) $request->integer('per_page', 10)),
        ]);
    }

    public function edit(Asset $asset): View
    {
        Gate::authorize('update', $asset);

        return view('admin.assets.form', ['asset' => $asset->load($this->assets->relations()), ...$this->formOptions()]);
    }

    public function update(UpdateAssetRequest $request, Asset $asset): RedirectResponse
    {
        Gate::authorize('update', $asset);
        $this->assets->update($asset, $request->validated());

        return redirect()->route('admin.assets.show', $asset)->with('success', 'Asset updated successfully.');
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        Gate::authorize('delete', $asset);
        $this->assets->delete($asset);

        return redirect()->route('admin.assets.index')->with('success', 'Asset archived successfully.');
    }

    public function photos(StoreAssetPhotoRequest $request, Asset $asset): RedirectResponse
    {
        Gate::authorize('update', $asset);
        $this->assets->addPhoto($asset, $request->validated(), $request->user());

        return redirect()->route('admin.assets.show', $asset)->with('success', 'Asset photo metadata added successfully.');
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('export', Asset::class);
        $records = $this->assets->records($request->only(['search', 'asset_category_id', 'building_id', 'floor_id', 'room_id', 'status', 'sort', 'direction']));

        return $this->web->csv(
            'assets',
            ['Asset Code', 'Category', 'Building', 'Floor', 'Room', 'Exact Location', 'Brand', 'Model', 'Serial Number', 'Purchase Date', 'Warranty Until', 'Status', 'Remarks'],
            $records,
            fn (Asset $asset): array => [
                $asset->asset_code,
                $asset->category?->name,
                $asset->building?->name,
                $asset->floor?->floor_name,
                $asset->room?->room_name,
                $asset->exact_location,
                $asset->brand,
                $asset->model,
                $asset->serial_number,
                $asset->purchase_date?->toDateString(),
                $asset->warranty_until?->toDateString(),
                $asset->status,
                $asset->remarks,
            ],
        );
    }

    private function formOptions(): array
    {
        return [
            'categories' => AssetCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'buildings' => Building::query()->where('is_active', true)->orderBy('name')->get(),
            'floors' => Floor::query()->with('building')->where('is_active', true)->orderBy('floor_name')->get(),
            'rooms' => Room::query()->with('floor.building')->where('is_active', true)->orderBy('room_name')->get(),
            'statuses' => Asset::STATUSES,
        ];
    }
}
