<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div><p class="text-sm font-medium text-emerald-700">Assets / Maintenance</p><h1 class="text-2xl font-semibold text-gray-900">Maintenance reviews</h1></div>
            <a href="{{ route('admin.maintenance-reviews.export', request()->query()) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Export CSV</a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            @foreach(['pending_review' => 'Pending Review', 'correction_requested' => 'Correction Requested', 'corrected' => 'Corrected', 'approved_this_month' => 'Approved This Month', 'rejected' => 'Rejected'] as $key => $label)
                <a href="{{ route('admin.maintenance-reviews.index', $key === 'approved_this_month' ? ['review_status' => 'approved'] : ['review_status' => $key]) }}" class="rounded-md border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-gray-500">{{ $label }}</p><p class="mt-2 text-3xl font-semibold text-gray-900">{{ $metrics[$key] }}</p>
                </a>
            @endforeach
        </section>

        <form method="GET" class="grid gap-3 rounded-md border border-gray-200 bg-white p-4 lg:grid-cols-4">
            <select name="review_status" class="rounded-md border-gray-300"><option value="">All review statuses</option>@foreach($statuses as $status)<option value="{{ $status }}" @selected(($filters['review_status'] ?? '') === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>@endforeach</select>
            <select name="asset_id" class="rounded-md border-gray-300"><option value="">All assets</option>@foreach($assets as $asset)<option value="{{ $asset->id }}" @selected((string)($filters['asset_id'] ?? '') === (string)$asset->id)>{{ $asset->asset_tag }} - {{ $asset->name }}</option>@endforeach</select>
            <select name="building_id" class="rounded-md border-gray-300"><option value="">All buildings</option>@foreach($buildings as $building)<option value="{{ $building->id }}" @selected((string)($filters['building_id'] ?? '') === (string)$building->id)>{{ $building->name }}</option>@endforeach</select>
            <select name="maintenance_type_id" class="rounded-md border-gray-300"><option value="">All maintenance types</option>@foreach($maintenanceTypes as $type)<option value="{{ $type->id }}" @selected((string)($filters['maintenance_type_id'] ?? '') === (string)$type->id)>{{ $type->name }}</option>@endforeach</select>
            <select name="creator_id" class="rounded-md border-gray-300"><option value="">All creators</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((string)($filters['creator_id'] ?? '') === (string)$user->id)>{{ $user->name }}</option>@endforeach</select>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="rounded-md border-gray-300">
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="rounded-md border-gray-300">
            <div class="flex gap-2"><button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white">Apply</button><a href="{{ route('admin.maintenance-reviews.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Reset</a></div>
        </form>

        <div class="overflow-hidden rounded-md border border-gray-200 bg-white">
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 text-sm"><thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left">Maintenance</th><th class="px-4 py-3 text-left">Asset</th><th class="px-4 py-3 text-left">Creator / Technician</th><th class="px-4 py-3 text-left">Review status</th><th class="px-4 py-3 text-right">History</th></tr></thead><tbody class="divide-y divide-gray-100">
                @forelse($records as $record)<tr><td class="px-4 py-3"><a href="{{ route('admin.maintenance-reviews.show', $record) }}" class="font-semibold text-emerald-700">{{ $record->maintenance_date?->toFormattedDateString() }}</a><p class="text-gray-500">{{ $record->maintenanceType?->name ?? 'General maintenance' }}</p></td><td class="px-4 py-3"><p class="font-medium text-gray-900">{{ $record->asset?->asset_tag }}</p><p class="text-gray-500">{{ $record->asset?->name }}</p></td><td class="px-4 py-3 text-gray-600">{{ $record->completedBy?->name ?? 'System' }}<span class="block text-xs text-gray-400">{{ $record->staffProfile?->user?->name ?? $record->performed_by }}</span></td><td class="px-4 py-3"><span class="rounded-md bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">{{ str($record->review_status)->replace('_', ' ')->title() }}</span></td><td class="px-4 py-3 text-right text-gray-600">{{ $record->review_actions_count }}</td></tr>
                @empty<tr><td colspan="5" class="px-4 py-12 text-center text-gray-500">No maintenance reviews match the selected filters.</td></tr>@endforelse
            </tbody></table></div>
            <div class="border-t border-gray-200 px-4 py-3">{{ $records->links() }}</div>
        </div>
    </div>
</x-app-layout>
