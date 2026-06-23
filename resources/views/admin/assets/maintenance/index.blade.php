<x-app-layout>
    <x-slot name="header">
        <div class="flex w-full items-center justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-emerald-700">Assets / Maintenance</p>
                <h1 class="text-2xl font-semibold text-gray-900">{{ $asset->asset_code }} maintenance</h1>
            </div>
            @can('create', App\Models\AssetMaintenanceRecord::class)
                <a href="{{ route('admin.assets.maintenance.create', $asset) }}" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Add record</a>
            @endcan
        </div>
    </x-slot>

    <div class="space-y-5">
        @if (session('success'))
            <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="flex flex-wrap items-center gap-3 text-sm">
            <a href="{{ route('admin.assets.show', $asset) }}" class="font-semibold text-gray-600 hover:text-gray-900">Asset details</a>
            <span class="text-gray-300">/</span>
            <span class="font-semibold text-emerald-700">Maintenance history</span>
        </div>

        <form method="GET" class="grid gap-3 border-b border-gray-200 pb-5 xl:grid-cols-[minmax(14rem,1fr)_13rem_10rem_10rem_10rem_11rem_9rem_auto]">
            <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search maintenance history" class="rounded-md border-gray-300 text-sm">
            <select name="maintenance_type_id" class="rounded-md border-gray-300 text-sm">
                <option value="">All types</option>
                @foreach ($maintenanceTypes as $type)
                    <option value="{{ $type->id }}" @selected((string) ($filters['maintenance_type_id'] ?? '') === (string) $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
            <input name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="rounded-md border-gray-300 text-sm">
            <input name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="rounded-md border-gray-300 text-sm">
            <select name="next_due" class="rounded-md border-gray-300 text-sm">
                <option value="">All follow-ups</option>
                <option value="upcoming" @selected(($filters['next_due'] ?? '') === 'upcoming')>Upcoming</option>
                <option value="overdue" @selected(($filters['next_due'] ?? '') === 'overdue')>Overdue</option>
            </select>
            <select name="sort" class="rounded-md border-gray-300 text-sm">
                <option value="maintenance_date" @selected(($filters['sort'] ?? 'maintenance_date') === 'maintenance_date')>Maintenance date</option>
                <option value="next_maintenance_date" @selected(($filters['sort'] ?? '') === 'next_maintenance_date')>Next maintenance</option>
                <option value="performed_by" @selected(($filters['sort'] ?? '') === 'performed_by')>Performed by</option>
                <option value="cost" @selected(($filters['sort'] ?? '') === 'cost')>Cost</option>
            </select>
            <select name="per_page" class="rounded-md border-gray-300 text-sm">
                @foreach ([10, 15, 25, 50] as $number)
                    <option value="{{ $number }}" @selected((int) ($filters['per_page'] ?? 10) === $number)>{{ $number }} rows</option>
                @endforeach
            </select>
            <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">Apply</button>
        </form>

        <div class="flex items-center justify-between gap-4">
            <p class="text-sm text-gray-500">{{ $records->total() }} {{ Str::plural('record', $records->total()) }}</p>
            @can('export', App\Models\AssetMaintenanceRecord::class)
                <a href="{{ route('admin.assets.maintenance.export', ['asset' => $asset] + request()->query()) }}" class="text-sm font-semibold text-gray-700 hover:text-emerald-700">Export CSV</a>
            @endcan
        </div>

        <div class="overflow-hidden rounded-md border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left">Date</th><th class="px-4 py-3 text-left">Type</th><th class="px-4 py-3 text-left">Performed by</th><th class="px-4 py-3 text-left">Next</th><th class="px-4 py-3 text-right">Cost</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($records as $record)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $record->maintenance_date?->format('Y-m-d') }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $record->maintenanceType?->name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $record->performed_by }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $record->next_maintenance_date?->format('Y-m-d') ?: 'Not set' }}</td>
                                <td class="px-4 py-3 text-right text-gray-700">{{ is_null($record->cost) ? '—' : number_format((float) $record->cost, 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <a href="{{ route('admin.assets.maintenance.show', [$asset, $record]) }}" class="font-medium text-gray-600 hover:text-gray-900">View</a>
                                    @can('update', $record)
                                        <a href="{{ route('admin.assets.maintenance.edit', [$asset, $record]) }}" class="ml-3 font-medium text-emerald-700 hover:text-emerald-900">Edit</a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-12 text-center text-gray-500">No maintenance records match the current filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $records->links() }}
    </div>
</x-app-layout>
