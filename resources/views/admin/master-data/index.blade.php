<x-app-layout>
    <x-slot name="header">
        <div class="flex w-full items-center justify-between gap-4">
            <div><p class="text-sm font-medium text-emerald-700">Master data</p><h1 class="text-2xl font-semibold text-gray-900">{{ $module['label'] }}</h1></div>
            <a href="{{ route("admin.master-data.{$moduleKey}.create") }}" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Add record</a>
        </div>
    </x-slot>

    <div class="space-y-5">
        @if (session('success'))<div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>@endif
        @include('admin.master-data.partials.tabs')

        <form method="GET" class="grid gap-3 border-b border-gray-200 pb-5 sm:grid-cols-2 xl:grid-cols-[minmax(16rem,1fr)_10rem_12rem_9rem_8rem_auto]">
            <label class="sr-only" for="search">Search</label>
            <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search {{ strtolower($module['label']) }}" class="rounded-md border-gray-300 text-sm">
            <select name="status" class="rounded-md border-gray-300 text-sm" aria-label="Status filter">
                <option value="">All statuses</option><option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option><option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
            </select>
            <select name="sort" class="rounded-md border-gray-300 text-sm" aria-label="Sort records">
                <option value="">Default order</option>
                @foreach ($module['columns'] as $key => $label) @if (! str_contains($key, '.'))<option value="{{ $key }}" @selected(($filters['sort'] ?? '') === $key)>Sort by {{ $label }}</option>@endif @endforeach
                <option value="is_active" @selected(($filters['sort'] ?? '') === 'is_active')>Sort by status</option>
                <option value="created_at" @selected(($filters['sort'] ?? '') === 'created_at')>Sort by created date</option>
            </select>
            <select name="direction" class="rounded-md border-gray-300 text-sm" aria-label="Sort direction"><option value="asc" @selected(($filters['direction'] ?? '') !== 'desc')>Ascending</option><option value="desc" @selected(($filters['direction'] ?? '') === 'desc')>Descending</option></select>
            <select name="per_page" class="rounded-md border-gray-300 text-sm" aria-label="Rows per page">@foreach ([10, 15, 25, 50] as $size)<option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 15) === $size)>{{ $size }} rows</option>@endforeach</select>
            <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">Apply</button>
        </form>

        <div class="flex items-center justify-between gap-4">
            <p class="text-sm text-gray-500">{{ $records->total() }} {{ Str::plural('record', $records->total()) }}</p>
            <a href="{{ route("admin.master-data.{$moduleKey}.export", request()->query()) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-700 hover:text-emerald-700" title="Export current results to CSV">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14" /></svg> Export CSV
            </a>
        </div>

        <div class="overflow-hidden rounded-md border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50"><tr>@foreach ($module['columns'] as $label)<th class="px-4 py-3 text-left font-semibold text-gray-600">{{ $label }}</th>@endforeach<th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th><th class="px-4 py-3 text-right font-semibold text-gray-600">Actions</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($records as $record)
                            <tr class="hover:bg-gray-50">
                                @foreach ($module['columns'] as $key => $label)
                                    <td class="max-w-xs px-4 py-3 text-gray-700">@if ($key === 'color' && $record->color)<span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full border" style="background: {{ $record->color }}"></span>{{ $record->color }}</span>@else{{ $web->displayValue($record, $key) }}@endif</td>
                                @endforeach
                                <td class="px-4 py-3"><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $record->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">{{ $record->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right"><a href="{{ route("admin.master-data.{$moduleKey}.show", $record) }}" class="font-medium text-gray-600 hover:text-gray-900">View</a><a href="{{ route("admin.master-data.{$moduleKey}.edit", $record) }}" class="ml-3 font-medium text-emerald-700 hover:text-emerald-900">Edit</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ count($module['columns']) + 2 }}" class="px-4 py-12 text-center text-gray-500">No records match the current filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ $records->links() }}
    </div>
</x-app-layout>
