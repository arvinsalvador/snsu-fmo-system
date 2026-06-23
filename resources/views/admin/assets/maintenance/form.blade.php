<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium text-emerald-700">Assets / Maintenance</p>
            <h1 class="text-2xl font-semibold text-gray-900">{{ $record ? 'Edit maintenance record' : 'Add maintenance record' }}</h1>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl">
        <form method="POST" action="{{ $record ? route('admin.assets.maintenance.update', [$asset, $record]) : route('admin.assets.maintenance.store', $asset) }}" class="space-y-6 rounded-md border border-gray-200 bg-white p-6">
            @csrf
            @if ($record) @method('PATCH') @endif

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Maintenance type</label>
                    <select name="maintenance_type_id" class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">Select type</option>
                        @foreach ($maintenanceTypes as $type)
                            <option value="{{ $type->id }}" @selected((string) old('maintenance_type_id', $record?->maintenance_type_id) === (string) $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                    @error('maintenance_type_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Maintenance date</label>
                    <input name="maintenance_date" type="date" value="{{ old('maintenance_date', $record?->maintenance_date?->toDateString() ?? now()->toDateString()) }}" class="w-full rounded-md border-gray-300 text-sm">
                    @error('maintenance_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Next maintenance date</label>
                    <input name="next_maintenance_date" type="date" value="{{ old('next_maintenance_date', $record?->next_maintenance_date?->toDateString()) }}" class="w-full rounded-md border-gray-300 text-sm">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Performed by</label>
                    <input name="performed_by" value="{{ old('performed_by', $record?->performed_by) }}" class="w-full rounded-md border-gray-300 text-sm">
                    @error('performed_by')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Cost</label>
                    <input name="cost" type="number" step="0.01" min="0" value="{{ old('cost', $record?->cost) }}" class="w-full rounded-md border-gray-300 text-sm">
                </div>
            </div>

            <div><label class="mb-1 block text-sm font-medium text-gray-700">Findings</label><textarea name="findings" rows="3" class="w-full rounded-md border-gray-300 text-sm">{{ old('findings', $record?->findings) }}</textarea></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Actions taken</label><textarea name="actions_taken" rows="3" class="w-full rounded-md border-gray-300 text-sm">{{ old('actions_taken', $record?->actions_taken) }}</textarea></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Remarks</label><textarea name="remarks" rows="3" class="w-full rounded-md border-gray-300 text-sm">{{ old('remarks', $record?->remarks) }}</textarea></div>

            <div class="flex justify-end gap-3 border-t border-gray-200 pt-5">
                <a href="{{ $record ? route('admin.assets.maintenance.show', [$asset, $record]) : route('admin.assets.maintenance.index', $asset) }}" class="px-3 py-2 text-sm font-semibold text-gray-600 hover:text-gray-900">Cancel</a>
                <button class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">{{ $record ? 'Save changes' : 'Create record' }}</button>
            </div>
        </form>
    </div>
</x-app-layout>
