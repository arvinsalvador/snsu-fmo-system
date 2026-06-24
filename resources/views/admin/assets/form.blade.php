<x-app-layout>
    <x-slot name="header"><div class="flex w-full items-center justify-between gap-4"><div><p class="text-sm font-medium text-emerald-700">Asset inventory</p><h1 class="text-2xl font-semibold text-gray-900">{{ $asset ? 'Edit asset' : 'Create asset' }}</h1></div><a href="{{ route('admin.assets.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Back</a></div></x-slot>
    <div class="py-6"><div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ $asset ? route('admin.assets.update', $asset) : route('admin.assets.store') }}" class="space-y-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            @csrf
            @if($asset) @method('PUT') @endif
            <div class="grid gap-4 md:grid-cols-2">
                <label class="block text-sm font-medium text-gray-700">Asset tag<input name="asset_tag" value="{{ old('asset_tag', $asset?->asset_tag) }}" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm"></label>
                <label class="block text-sm font-medium text-gray-700">Name<input name="name" value="{{ old('name', $asset?->name) }}" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm"></label>
                <label class="block text-sm font-medium text-gray-700">Category<select name="asset_category_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm"><option value="">Uncategorized</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) old('asset_category_id', $asset?->asset_category_id) === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></label>
                <label class="block text-sm font-medium text-gray-700">Status<select name="status" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm">@foreach($statuses as $status)<option value="{{ $status }}" @selected(old('status', $asset?->status ?? 'active') === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>@endforeach</select></label>
                <label class="block text-sm font-medium text-gray-700">Building<select name="building_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm"><option value="">Unassigned</option>@foreach($buildings as $building)<option value="{{ $building->id }}" @selected((string) old('building_id', $asset?->building_id) === (string) $building->id)>{{ $building->name }}</option>@endforeach</select></label>
                <label class="block text-sm font-medium text-gray-700">Floor<select name="floor_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm"><option value="">Unassigned</option>@foreach($floors as $floor)<option value="{{ $floor->id }}" @selected((string) old('floor_id', $asset?->floor_id) === (string) $floor->id)>{{ $floor->floor_name }}</option>@endforeach</select></label>
                <label class="block text-sm font-medium text-gray-700">Room<select name="room_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm"><option value="">Unassigned</option>@foreach($rooms as $room)<option value="{{ $room->id }}" @selected((string) old('room_id', $asset?->room_id) === (string) $room->id)>{{ $room->room_code }} - {{ $room->name }}</option>@endforeach</select></label>
                <label class="block text-sm font-medium text-gray-700">Exact location<input name="location" value="{{ old('location', $asset?->location) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm"></label>
                <label class="block text-sm font-medium text-gray-700">Brand<input name="brand" value="{{ old('brand', $asset?->brand) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm"></label>
                <label class="block text-sm font-medium text-gray-700">Model<input name="model" value="{{ old('model', $asset?->model) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm"></label>
                <label class="block text-sm font-medium text-gray-700">Serial number<input name="serial_number" value="{{ old('serial_number', $asset?->serial_number) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm"></label>
                <label class="block text-sm font-medium text-gray-700">Purchase date<input type="date" name="purchase_date" value="{{ old('purchase_date', $asset?->purchase_date?->toDateString()) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm"></label>
                <label class="block text-sm font-medium text-gray-700">Warranty until<input type="date" name="warranty_until" value="{{ old('warranty_until', $asset?->warranty_until?->toDateString()) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm"></label>
            </div>
            <label class="block text-sm font-medium text-gray-700">Remarks<textarea name="remarks" rows="3" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">{{ old('remarks', $asset?->remarks) }}</textarea></label>
            @if($errors->any())<div class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <div class="flex justify-end"><button class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Save asset</button></div>
        </form>
    </div></div>
</x-app-layout>
