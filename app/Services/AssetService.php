<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetPhoto;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AssetService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($this->perPage($filters))->withQueryString();
    }

    public function records(array $filters = []): Collection
    {
        return $this->query($filters)->get();
    }

    public function lookup(string $search = '', int $limit = 50): Collection
    {
        return Asset::query()
            ->select(['id', 'uuid', 'asset_tag', 'name', 'building_id', 'floor_id', 'room_id', 'status'])
            ->when($search, fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('asset_tag', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")))
            ->orderBy('asset_tag')
            ->limit(min(max($limit, 1), 100))
            ->get();
    }

    public function create(array $data): Asset
    {
        return DB::transaction(fn (): Asset => Asset::query()->create($this->normalize($data))->load($this->relations()));
    }

    public function update(Asset $asset, array $data): Asset
    {
        return DB::transaction(function () use ($asset, $data): Asset {
            $asset->update($this->normalize($data));

            return $asset->refresh()->load($this->relations());
        });
    }

    public function delete(Asset $asset): void
    {
        DB::transaction(fn () => $asset->delete());
    }

    public function addPhoto(Asset $asset, array $data, ?User $user = null): AssetPhoto
    {
        return DB::transaction(fn (): AssetPhoto => $asset->photos()->create([
            ...$data,
            'uploaded_by' => $user?->id,
        ])->load('uploader'));
    }

    /** @return array<int, string> */
    public function relations(): array
    {
        return ['category', 'building', 'floor', 'room', 'photos.uploader', 'maintenanceSchedules'];
    }

    private function query(array $filters): Builder
    {
        $sort = in_array($filters['sort'] ?? '', ['asset_tag', 'name', 'brand', 'model', 'serial_number', 'purchase_date', 'warranty_until', 'status', 'created_at'], true) ? $filters['sort'] : 'asset_tag';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return Asset::query()
            ->with(['category', 'building', 'floor', 'room'])
            ->withCount(['maintenanceSchedules', 'maintenanceRecords'])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query
                ->where('asset_tag', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('brand', 'like', "%{$search}%")
                ->orWhere('model', 'like', "%{$search}%")
                ->orWhere('serial_number', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%")))
            ->when($filters['asset_category_id'] ?? null, fn (Builder $query, int|string $categoryId) => $query->where('asset_category_id', $categoryId))
            ->when($filters['building_id'] ?? null, fn (Builder $query, int|string $buildingId) => $query->where('building_id', $buildingId))
            ->when($filters['floor_id'] ?? null, fn (Builder $query, int|string $floorId) => $query->where('floor_id', $floorId))
            ->when($filters['room_id'] ?? null, fn (Builder $query, int|string $roomId) => $query->where('room_id', $roomId))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy($sort, $direction);
    }

    private function normalize(array $data): array
    {
        if (isset($data['asset_code']) && ! isset($data['asset_tag'])) {
            $data['asset_tag'] = $data['asset_code'];
        }

        if (isset($data['exact_location']) && ! isset($data['location'])) {
            $data['location'] = $data['exact_location'];
        }

        unset($data['asset_code'], $data['exact_location']);

        return $data;
    }

    private function perPage(array $filters): int
    {
        return min(max((int) ($filters['per_page'] ?? 15), 10), 100);
    }
}
