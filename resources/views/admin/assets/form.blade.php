<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium text-emerald-700">Administration / Assets</p>
            <h1 class="text-2xl font-semibold text-gray-900">{{ $asset ? 'Edit asset' : 'Add asset' }}</h1>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl">
        <form method="POST" action="{{ $asset ? route('admin.assets.update', $asset) : route('admin.assets.store') }}" class="space-y-6 rounded-md border border-gray-200 bg-white p-6">
            @csrf
            @if ($asset) @method('PUT') @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Asset code</label>
                    <input name="asset_code" value="{{ old('asset_code', $asset?->asset_code) }}" class="w-full rounded-md border-gray-300 text-sm">
                    @error('asset_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Category</label>
                    <select name="asset_category_id" class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">Select category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('asset_category_id', $asset?->asset_category_id) === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('asset_category_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Building</label>
                    <select name="building_id" class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">Select building</option>
                        @foreach ($buildings as $building)
                            <option value="{{ $building->id }}" @selected((string) old('building_id', $asset?->building_id) === (string) $building->id)>{{ $building->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Floor</label>
                    <select name="floor_id" class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">Select floor</option>
                        @foreach ($floors as $floor)
                            <option value="{{ $floor->id }}" @selected((string) old('floor_id', $asset?->floor_id) === (string) $floor->id)>{{ $floor->floor_name }}{{ $floor->building ? ' / '.$floor->building->name : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Room</label>
                    <select name="room_id" class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">Select room</option>
                        @foreach ($rooms as $room)
                            <option value="{{ $room->id }}" @selected((string) old('room_id', $asset?->room_id) === (string) $room->id)>{{ $room->room_name }}{{ $room->floor?->building ? ' / '.$room->floor->building->name : '' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Exact location</label>
                <input name="exact_location" value="{{ old('exact_location', $asset?->exact_location) }}" class="w-full rounded-md border-gray-300 text-sm" placeholder="Example: North hallway, beside main entrance">
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div><label class="mb-1 block text-sm font-medium text-gray-700">Brand</label><input name="brand" value="{{ old('brand', $asset?->brand) }}" class="w-full rounded-md border-gray-300 text-sm"></div>
                <div><label class="mb-1 block text-sm font-medium text-gray-700">Model</label><input name="model" value="{{ old('model', $asset?->model) }}" class="w-full rounded-md border-gray-300 text-sm"></div>
                <div><label class="mb-1 block text-sm font-medium text-gray-700">Serial number</label><input name="serial_number" value="{{ old('serial_number', $asset?->serial_number) }}" class="w-full rounded-md border-gray-300 text-sm"></div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div><label class="mb-1 block text-sm font-medium text-gray-700">Purchase date</label><input name="purchase_date" type="date" value="{{ old('purchase_date', $asset?->purchase_date?->toDateString()) }}" class="w-full rounded-md border-gray-300 text-sm"></div>
                <div><label class="mb-1 block text-sm font-medium text-gray-700">Warranty until</label><input name="warranty_until" type="date" value="{{ old('warranty_until', $asset?->warranty_until?->toDateString()) }}" class="w-full rounded-md border-gray-300 text-sm"></div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" class="w-full rounded-md border-gray-300 text-sm">
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', $asset?->status ?? 'Active') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Remarks</label>
                <textarea name="remarks" rows="4" class="w-full rounded-md border-gray-300 text-sm">{{ old('remarks', $asset?->remarks) }}</textarea>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-200 pt-5">
                <a href="{{ $asset ? route('admin.assets.show', $asset) : route('admin.assets.index') }}" class="px-3 py-2 text-sm font-semibold text-gray-600 hover:text-gray-900">Cancel</a>
                <button class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">{{ $asset ? 'Save changes' : 'Create asset' }}</button>
            </div>
        </form>
    </div>
</x-app-layout>
