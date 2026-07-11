<x-app-layout>
    <x-slot name="header">
        <div class="flex w-full items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ $record->completion_date->format('M j, Y') }}</p>
                <h1 class="text-2xl font-semibold text-gray-900">Maintenance completion</h1>
            </div>
            <div class="flex gap-2">
                @can('update', $record)
                    <a href="{{ route('admin.asset-maintenance.edit', $record) }}" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Edit</a>
                @endcan
                <a href="{{ route('admin.assets.show', $record->asset) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Asset history</a>
            </div>
        </div>
    </x-slot>

    <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_320px]">
        <div class="space-y-7">
            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">Actions taken</h2>
                <p class="mt-4 whitespace-pre-line leading-7 text-gray-700">{{ $record->actions_taken }}</p>
            </section>
            @if($record->findings)<section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm"><h2 class="text-lg font-semibold text-gray-900">Findings</h2><p class="mt-4 whitespace-pre-line leading-7 text-gray-700">{{ $record->findings }}</p></section>@endif
            @if($record->remarks)<section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm"><h2 class="text-lg font-semibold text-gray-900">Remarks</h2><p class="mt-4 whitespace-pre-line leading-7 text-gray-700">{{ $record->remarks }}</p></section>@endif
        </div>

        <aside>
            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-gray-900">Record details</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="text-gray-500">Asset</dt><dd class="font-medium text-gray-900">{{ $record->asset?->asset_tag }} - {{ $record->asset?->name }}</dd></div>
                    <div><dt class="text-gray-500">Maintenance type</dt><dd class="font-medium text-gray-900">{{ $record->maintenanceType?->name ?? 'Not specified' }}</dd></div>
                    <div><dt class="text-gray-500">Technician</dt><dd class="font-medium text-gray-900">{{ $record->staffProfile?->user?->name ?? 'Not assigned' }}</dd></div>
                    <div><dt class="text-gray-500">Performed by</dt><dd class="font-medium text-gray-900">{{ $record->performed_by ?? 'Not recorded' }}</dd></div>
                    <div><dt class="text-gray-500">Schedule</dt><dd class="font-medium text-gray-900">{{ $record->maintenanceSchedule?->title ?? 'Not scheduled' }}</dd></div>
                    <div><dt class="text-gray-500">Work order</dt><dd class="font-medium text-gray-900">{{ $record->workOrder?->work_order_number ?? 'Not linked' }}</dd></div>
                    <div><dt class="text-gray-500">Labor / total cost</dt><dd class="font-medium text-gray-900">{{ $record->labor_cost ? number_format((float) $record->labor_cost, 2) : '-' }} / {{ $record->total_cost ? number_format((float) $record->total_cost, 2) : '-' }}</dd></div>
                    <div><dt class="text-gray-500">Next maintenance</dt><dd class="font-medium text-gray-900">{{ $record->next_maintenance_date?->toFormattedDateString() ?? 'Not scheduled' }}</dd></div>
                    <div><dt class="text-gray-500">Recorded by</dt><dd class="font-medium text-gray-900">{{ $record->completedBy?->name ?? 'System' }}</dd></div>
                </dl>
            </section>
        </aside>
    </div>
</x-app-layout>
