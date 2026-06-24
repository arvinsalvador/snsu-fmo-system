@extends('layouts.app')

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Preventive Maintenance</h1>
            <p class="mt-1 text-sm text-zinc-600">Schedule, track, and complete recurring asset maintenance.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('maintenance-schedules.export', request()->query()) }}" class="rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100">Export CSV</a>
            <a href="{{ route('maintenance-schedules.create') }}" class="rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800">New Schedule</a>
        </div>
    </div>

    <section class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([['Active', $metrics['active']], ['Upcoming', $metrics['upcoming']], ['Overdue', $metrics['overdue']], ['Inactive', $metrics['inactive']]] as [$label, $value])
            <div class="rounded-md border border-zinc-200 bg-white p-4 shadow-sm">
                <div class="text-xs font-semibold uppercase text-zinc-500">{{ $label }}</div>
                <div class="mt-2 text-3xl font-semibold tracking-tight">{{ $value }}</div>
            </div>
        @endforeach
    </section>

    <section class="mb-6 grid gap-4 lg:grid-cols-2">
        <div class="rounded-md border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 px-4 py-3 text-sm font-semibold">Upcoming Maintenance</div>
            <div class="divide-y divide-zinc-100">
                @forelse ($upcoming as $item)
                    <a href="{{ route('maintenance-schedules.show', $item) }}" class="flex items-center justify-between gap-4 px-4 py-3 text-sm hover:bg-zinc-50">
                        <span><span class="font-medium text-zinc-950">{{ $item->title }}</span><span class="block text-zinc-500">{{ $item->asset->asset_tag }} - {{ $item->asset->name }}</span></span>
                        <span class="shrink-0 text-zinc-600">{{ $item->next_due_date->format('M d') }}</span>
                    </a>
                @empty
                    <div class="px-4 py-6 text-sm text-zinc-500">No maintenance due in the next 30 days.</div>
                @endforelse
            </div>
        </div>

        <div class="rounded-md border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 px-4 py-3 text-sm font-semibold">Overdue Maintenance</div>
            <div class="divide-y divide-zinc-100">
                @forelse ($overdue as $item)
                    <a href="{{ route('maintenance-schedules.show', $item) }}" class="flex items-center justify-between gap-4 px-4 py-3 text-sm hover:bg-zinc-50">
                        <span><span class="font-medium text-zinc-950">{{ $item->title }}</span><span class="block text-zinc-500">{{ $item->asset->asset_tag }} - {{ $item->asset->name }}</span></span>
                        <span class="shrink-0 text-red-600">{{ $item->next_due_date->diffForHumans() }}</span>
                    </a>
                @empty
                    <div class="px-4 py-6 text-sm text-zinc-500">No overdue preventive maintenance.</div>
                @endforelse
            </div>
        </div>
    </section>

    <form method="GET" action="{{ route('maintenance-schedules.index') }}" class="mb-4 grid gap-3 rounded-md border border-zinc-200 bg-white p-4 shadow-sm lg:grid-cols-6">
        <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search schedules or assets" class="rounded-md border border-zinc-300 px-3 py-2 text-sm lg:col-span-2">
        <select name="frequency" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm">
            <option value="">All frequencies</option>
            @foreach (array_keys($frequencies) as $frequency)
                <option value="{{ $frequency }}" @selected(($filters['frequency'] ?? '') === $frequency)>{{ str($frequency)->title() }}</option>
            @endforeach
        </select>
        <select name="asset_id" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm">
            <option value="">All assets</option>
            @foreach ($assets as $asset)
                <option value="{{ $asset->id }}" @selected((string) ($filters['asset_id'] ?? '') === (string) $asset->id)>{{ $asset->asset_tag }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm">
            <option value="">All statuses</option>
            @foreach (['upcoming', 'overdue', 'inactive'] as $status)
                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ str($status)->title() }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button class="flex-1 rounded-md bg-zinc-950 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-800">Filter</button>
            <a href="{{ route('maintenance-schedules.index') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100">Reset</a>
        </div>
    </form>

    <div class="overflow-hidden rounded-md border border-zinc-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-zinc-100 text-left text-xs font-semibold uppercase text-zinc-500">
                    <tr>
                        <th class="px-4 py-3">Schedule</th>
                        <th class="px-4 py-3">Asset</th>
                        <th class="px-4 py-3">Frequency</th>
                        <th class="px-4 py-3">Next Due</th>
                        <th class="px-4 py-3">Last Done</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($schedules as $schedule)
                        <tr class="align-top">
                            <td class="px-4 py-3"><a href="{{ route('maintenance-schedules.show', $schedule) }}" class="font-medium text-zinc-950 hover:underline">{{ $schedule->title }}</a></td>
                            <td class="px-4 py-3 text-zinc-600">{{ $schedule->asset->asset_tag }}<span class="block text-zinc-500">{{ $schedule->asset->name }}</span></td>
                            <td class="px-4 py-3 text-zinc-600">{{ $schedule->frequency_label }}</td>
                            <td class="px-4 py-3 text-zinc-600">{{ $schedule->next_due_date->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-zinc-600">{{ $schedule->last_completed_date?->format('M d, Y') ?? 'Not recorded' }}</td>
                            <td class="px-4 py-3"><span class="rounded-md px-2 py-1 text-xs font-semibold {{ $schedule->due_status === 'overdue' ? 'bg-red-50 text-red-700' : ($schedule->due_status === 'upcoming' ? 'bg-amber-50 text-amber-700' : 'bg-zinc-100 text-zinc-700') }}">{{ str($schedule->due_status)->title() }}</span></td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <form method="POST" action="{{ route('maintenance-schedules.complete', $schedule) }}">@csrf<button class="rounded-md border border-emerald-300 px-2 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Complete</button></form>
                                    <a href="{{ route('maintenance-schedules.edit', $schedule) }}" class="rounded-md border border-zinc-300 px-2 py-1 text-xs font-semibold text-zinc-700 hover:bg-zinc-100">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-zinc-500">No maintenance schedules match the current filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-zinc-200 px-4 py-3">{{ $schedules->links() }}</div>
    </div>
@endsection
