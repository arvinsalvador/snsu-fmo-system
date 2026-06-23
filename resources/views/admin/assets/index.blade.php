<x-app-layout>
    <x-slot name="header">
        <div class="flex w-full items-center justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-emerald-700">Administration / Assets</p>
                <h1 class="text-2xl font-semibold text-gray-900">Asset registry</h1>
            </div>
            @can('create', App\Models\Asset::class)
                <a href="{{ route('admin.assets.create') }}" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Add asset</a>
            @endcan
        </div>
    </x-slot>

    <div class="space-y-5">
        @if (session('success'))
            <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <form method="GET" class="grid gap-3 border-b border-gray-200 pb-5 xl:grid-cols-[minmax(14rem,1fr)_12rem_12rem_12rem_10rem_11rem_9rem_auto]">
            <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search assets" class="rounded-md border-gray-300 text-sm">
            <select name="asset_category_id" class="rounded-md border-gray-300 text-sm">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) ($filters['asset_category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="building_id" class="rounded-md border-gray-300 text-sm">
                <option value="">All buildings</option>
                @foreach ($buildings as $building)
                    <option value="{{ $building->id }}" @selected((string) ($filters['building_id'] ?? '') === (string) $building->id)>{{ $building->name }}</option>
                @endforeach
            </select>
            <select name="room_id" class="rounded-md border-gray-300 text-sm">
                <option value="">All rooms</option>
                @foreach ($rooms as $room)
                    <option value="{{ $room->id }}" @selected((string) ($filters['room_id'] ?? '') === (string) $room->id)>{{ $room->room_name }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-md border-gray-300 text-sm">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <select name="sort" class="rounded-md border-gray-300 text-sm">
                <option value="asset_code" @selected(($filters['sort'] ?? 'asset_code') === 'asset_code')>Asset code</option>
                <option value="brand" @selected(($filters['sort'] ?? '') === 'brand')>Brand</option>
                <option value="model" @selected(($filters['sort'] ?? '') === 'model')>Model</option>
                <option value="status" @selected(($filters['sort'] ?? '') === 'status')>Status</option>
                <option value="purchase_date" @selected(($filters['sort'] ?? '') === 'purchase_date')>Purchase date</option>
                <option value="created_at" @selected(($filters['sort'] ?? '') === 'created_at')>Created date</option>
            </select>
            <select name="per_page" class="rounded-md border-gray-300 text-sm">
                @foreach ([10, 15, 25, 50] as $number)
                    <option value="{{ $number }}" @selected((int) ($filters['per_page'] ?? 15) === $number)>{{ $number }} rows</option>
                @endforeach
            </select>
            <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">Apply</button>
        </form>

        <div class="flex items-center justify-between gap-4">
            <p class="text-sm text-gray-500">{{ $assets->total() }} {{ Str::plural('asset', $assets->total()) }}</p>
            @can('export', App\Models\Asset::class)
                <a href="{{ route('admin.assets.export', request()->query()) }}" class="text-sm font-semibold text-gray-700 hover:text-emerald-700">Export CSV</a>
            @endcan
        </div>

        <div class="overflow-hidden rounded-md border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Asset</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Category</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Location</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($assets as $asset)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-gray-900">{{ $asset->asset_code }}</p>
                                    <p class="text-gray-500">{{ collect([$asset->brand, $asset->model, $asset->serial_number])->filter()->join(' / ') ?: 'No equipment details' }}</p>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $asset->category?->name ?: 'Uncategorized' }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    <p>{{ collect([$asset->building?->name, $asset->floor?->floor_name, $asset->room?->room_name])->filter()->join(' / ') ?: 'No location' }}</p>
                                    @if ($asset->exact_location)<p class="text-xs text-gray-500">{{ $asset->exact_location }}</p>@endif
                                </td>
                                <td class="px-4 py-3"><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $asset->status === 'Active' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">{{ $asset->status }}</span></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <a href="{{ route('admin.assets.show', $asset) }}" class="font-medium text-gray-600 hover:text-gray-900">View</a>
                                    @can('update', $asset)
                                        <a href="{{ route('admin.assets.edit', $asset) }}" class="ml-3 font-medium text-emerald-700 hover:text-emerald-900">Edit</a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-12 text-center text-gray-500">No assets match the current filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $assets->links() }}
    </div>
</x-app-layout>
