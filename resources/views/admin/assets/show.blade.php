<x-app-layout>
    <x-slot name="header">
        <div class="flex w-full items-center justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-emerald-700">Administration / Assets</p>
                <h1 class="text-2xl font-semibold text-gray-900">{{ $asset->asset_code }}</h1>
            </div>
            <div class="flex items-center gap-2">
                @can('update', $asset)
                    <a href="{{ route('admin.assets.edit', $asset) }}" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Edit asset</a>
                @endcan
                @can('delete', $asset)
                    <form method="POST" action="{{ route('admin.assets.destroy', $asset) }}" onsubmit="return confirm('Archive this asset?');">
                        @csrf
                        @method('DELETE')
                        <button class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Archive</button>
                    </form>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="space-y-5">
        @if (session('success'))
            <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="grid gap-5 lg:grid-cols-[1fr_24rem]">
            <div class="rounded-md border border-gray-200 bg-white">
                <dl class="grid sm:grid-cols-2">
                    <div class="border-b border-gray-100 px-5 py-4"><dt class="text-xs font-semibold uppercase text-gray-500">Category</dt><dd class="mt-1 text-sm font-medium text-gray-900">{{ $asset->category?->name ?: 'Uncategorized' }}</dd></div>
                    <div class="border-b border-gray-100 px-5 py-4"><dt class="text-xs font-semibold uppercase text-gray-500">Status</dt><dd class="mt-1"><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $asset->status === 'Active' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">{{ $asset->status }}</span></dd></div>
                    <div class="border-b border-gray-100 px-5 py-4"><dt class="text-xs font-semibold uppercase text-gray-500">Brand</dt><dd class="mt-1 text-sm text-gray-700">{{ $asset->brand ?: 'Not set' }}</dd></div>
                    <div class="border-b border-gray-100 px-5 py-4"><dt class="text-xs font-semibold uppercase text-gray-500">Model</dt><dd class="mt-1 text-sm text-gray-700">{{ $asset->model ?: 'Not set' }}</dd></div>
                    <div class="border-b border-gray-100 px-5 py-4"><dt class="text-xs font-semibold uppercase text-gray-500">Serial number</dt><dd class="mt-1 text-sm text-gray-700">{{ $asset->serial_number ?: 'Not set' }}</dd></div>
                    <div class="border-b border-gray-100 px-5 py-4"><dt class="text-xs font-semibold uppercase text-gray-500">Warranty until</dt><dd class="mt-1 text-sm text-gray-700">{{ $asset->warranty_until?->format('Y-m-d') ?: 'Not set' }}</dd></div>
                    <div class="border-b border-gray-100 px-5 py-4 sm:col-span-2"><dt class="text-xs font-semibold uppercase text-gray-500">Location</dt><dd class="mt-1 text-sm text-gray-700">{{ collect([$asset->building?->name, $asset->floor?->floor_name, $asset->room?->room_name])->filter()->join(' / ') ?: 'No location' }}@if($asset->exact_location)<span class="block text-gray-500">{{ $asset->exact_location }}</span>@endif</dd></div>
                    <div class="px-5 py-4 sm:col-span-2"><dt class="text-xs font-semibold uppercase text-gray-500">Remarks</dt><dd class="mt-1 text-sm text-gray-700">{{ $asset->remarks ?: 'No remarks provided.' }}</dd></div>
                </dl>
            </div>

            @can('update', $asset)
                <form method="POST" action="{{ route('admin.assets.photos.store', $asset) }}" class="space-y-4 rounded-md border border-gray-200 bg-white p-5">
                    @csrf
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Photo metadata</h2>
                        <p class="mt-1 text-xs text-gray-500">Stores upload metadata only; full media processing is reserved for a later phase.</p>
                    </div>
                    <input name="image_path" class="w-full rounded-md border-gray-300 text-sm" placeholder="Storage path">
                    <input name="caption" class="w-full rounded-md border-gray-300 text-sm" placeholder="Caption">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <input name="original_name" class="rounded-md border-gray-300 text-sm" placeholder="Original filename">
                        <input name="mime_type" class="rounded-md border-gray-300 text-sm" placeholder="MIME type">
                    </div>
                    <input name="size" type="number" min="0" class="w-full rounded-md border-gray-300 text-sm" placeholder="Size in bytes">
                    <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">Add photo record</button>
                </form>
            @endcan
        </div>

        @can('viewAny', App\Models\AssetMaintenanceRecord::class)
            <div class="rounded-md border border-gray-200 bg-white">
                <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-5 py-4">
                    <h2 class="text-sm font-semibold text-gray-900">Asset maintenance</h2>
                    <div class="flex items-center gap-3 text-sm">
                        <a href="{{ route('admin.assets.maintenance.index', $asset) }}" class="font-semibold text-gray-700 hover:text-emerald-700">View all</a>
                        @can('create', App\Models\AssetMaintenanceRecord::class)
                            <a href="{{ route('admin.assets.maintenance.create', $asset) }}" class="font-semibold text-emerald-700 hover:text-emerald-900">Add record</a>
                        @endcan
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left">Date</th><th class="px-4 py-3 text-left">Type</th><th class="px-4 py-3 text-left">Performed by</th><th class="px-4 py-3 text-left">Next</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($maintenanceRecords as $record)
                                <tr><td class="px-4 py-3 font-medium text-gray-900">{{ $record->maintenance_date?->format('Y-m-d') }}</td><td class="px-4 py-3 text-gray-700">{{ $record->maintenanceType?->name }}</td><td class="px-4 py-3 text-gray-600">{{ $record->performed_by }}</td><td class="px-4 py-3 text-gray-600">{{ $record->next_maintenance_date?->format('Y-m-d') ?: 'Not set' }}</td><td class="px-4 py-3 text-right"><a href="{{ route('admin.assets.maintenance.show', [$asset, $record]) }}" class="font-medium text-gray-600 hover:text-gray-900">View</a></td></tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">No maintenance records yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-md border border-gray-200 bg-white">
                <div class="border-b border-gray-200 px-5 py-4"><h2 class="text-sm font-semibold text-gray-900">Maintenance timeline</h2></div>
                <div class="divide-y divide-gray-100">
                    @forelse ($maintenanceTimeline as $event)
                        <div class="px-5 py-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div><p class="text-sm font-semibold text-gray-900">{{ $event['title'] }}</p><p class="mt-1 text-sm text-gray-600">{{ $event['body'] ?: 'No details recorded.' }}</p></div>
                                <p class="text-xs font-medium text-gray-500">{{ $event['occurred_at']?->format('Y-m-d') }}</p>
                            </div>
                            <p class="mt-2 text-xs text-gray-500">{{ $event['actor'] }}@if($event['meta']) - {{ $event['meta'] }}@endif</p>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-center text-sm text-gray-500">No maintenance timeline events yet.</div>
                    @endforelse
                </div>
            </div>
        @endcan

        <div class="rounded-md border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-5 py-4"><h2 class="text-sm font-semibold text-gray-900">Photo records</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left">Path</th><th class="px-4 py-3 text-left">Caption</th><th class="px-4 py-3 text-left">Uploaded by</th><th class="px-4 py-3 text-left">Added</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($photos as $photo)
                            <tr><td class="px-4 py-3 font-medium text-gray-900">{{ $photo->image_path }}</td><td class="px-4 py-3 text-gray-600">{{ $photo->caption ?: 'No caption' }}</td><td class="px-4 py-3 text-gray-600">{{ $photo->uploader?->name ?: 'Unknown' }}</td><td class="px-4 py-3 text-gray-600">{{ $photo->created_at?->format('Y-m-d H:i') }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-10 text-center text-gray-500">No photo metadata recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4">{{ $photos->links() }}</div>
        </div>
    </div>
</x-app-layout>
