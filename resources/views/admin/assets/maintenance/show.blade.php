<x-app-layout>
    <x-slot name="header">
        <div class="flex w-full items-center justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-emerald-700">Assets / Maintenance</p>
                <h1 class="text-2xl font-semibold text-gray-900">{{ $record->maintenanceType?->name }} - {{ $record->maintenance_date?->format('Y-m-d') }}</h1>
            </div>
            @can('update', $record)
                <a href="{{ route('admin.assets.maintenance.edit', [$asset, $record]) }}" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Edit record</a>
            @endcan
        </div>
    </x-slot>

    <div class="space-y-5">
        @if (session('success'))
            <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="flex flex-wrap items-center gap-3 text-sm">
            <a href="{{ route('admin.assets.show', $asset) }}" class="font-semibold text-gray-600 hover:text-gray-900">{{ $asset->asset_code }}</a>
            <span class="text-gray-300">/</span>
            <a href="{{ route('admin.assets.maintenance.index', $asset) }}" class="font-semibold text-gray-600 hover:text-gray-900">Maintenance history</a>
            <span class="text-gray-300">/</span>
            <span class="font-semibold text-emerald-700">Record details</span>
        </div>

        <div class="rounded-md border border-gray-200 bg-white">
            <dl class="grid sm:grid-cols-2">
                <div class="border-b border-gray-100 px-5 py-4"><dt class="text-xs font-semibold uppercase text-gray-500">Maintenance type</dt><dd class="mt-1 text-sm font-medium text-gray-900">{{ $record->maintenanceType?->name }}</dd></div>
                <div class="border-b border-gray-100 px-5 py-4"><dt class="text-xs font-semibold uppercase text-gray-500">Maintenance date</dt><dd class="mt-1 text-sm text-gray-700">{{ $record->maintenance_date?->format('Y-m-d') }}</dd></div>
                <div class="border-b border-gray-100 px-5 py-4"><dt class="text-xs font-semibold uppercase text-gray-500">Performed by</dt><dd class="mt-1 text-sm text-gray-700">{{ $record->performed_by }}</dd></div>
                <div class="border-b border-gray-100 px-5 py-4"><dt class="text-xs font-semibold uppercase text-gray-500">Cost</dt><dd class="mt-1 text-sm text-gray-700">{{ is_null($record->cost) ? 'Not recorded' : number_format((float) $record->cost, 2) }}</dd></div>
                <div class="border-b border-gray-100 px-5 py-4"><dt class="text-xs font-semibold uppercase text-gray-500">Next maintenance</dt><dd class="mt-1 text-sm text-gray-700">{{ $record->next_maintenance_date?->format('Y-m-d') ?: 'Not set' }}</dd></div>
                <div class="border-b border-gray-100 px-5 py-4"><dt class="text-xs font-semibold uppercase text-gray-500">Recorded by</dt><dd class="mt-1 text-sm text-gray-700">{{ $record->recorder?->name ?: 'Unknown' }}</dd></div>
                <div class="border-b border-gray-100 px-5 py-4 sm:col-span-2"><dt class="text-xs font-semibold uppercase text-gray-500">Findings</dt><dd class="mt-1 whitespace-pre-line text-sm text-gray-700">{{ $record->findings ?: 'No findings recorded.' }}</dd></div>
                <div class="border-b border-gray-100 px-5 py-4 sm:col-span-2"><dt class="text-xs font-semibold uppercase text-gray-500">Actions taken</dt><dd class="mt-1 whitespace-pre-line text-sm text-gray-700">{{ $record->actions_taken ?: 'No actions recorded.' }}</dd></div>
                <div class="px-5 py-4 sm:col-span-2"><dt class="text-xs font-semibold uppercase text-gray-500">Remarks</dt><dd class="mt-1 whitespace-pre-line text-sm text-gray-700">{{ $record->remarks ?: 'No remarks provided.' }}</dd></div>
            </dl>
        </div>
    </div>
</x-app-layout>
