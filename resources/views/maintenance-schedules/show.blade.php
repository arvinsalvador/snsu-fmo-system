<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold tracking-tight">Maintenance Schedule</h1></x-slot>
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">{{ $schedule->title }}</h1>
            <p class="mt-1 text-sm text-zinc-600">{{ $schedule->asset->asset_tag }} - {{ $schedule->asset->name }}</p>
        </div>
        @can('update', $schedule)<a href="{{ route('maintenance-schedules.edit', $schedule) }}" class="rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800">Edit</a>@endcan
    </div>

    <dl class="grid gap-4 rounded-md border border-zinc-200 bg-white p-5 shadow-sm md:grid-cols-3">
        <div><dt class="text-xs font-semibold uppercase text-zinc-500">Status</dt><dd class="mt-1 text-sm font-medium text-zinc-950">{{ str($schedule->due_status)->title() }}</dd></div>
        <div><dt class="text-xs font-semibold uppercase text-zinc-500">Frequency</dt><dd class="mt-1 text-sm font-medium text-zinc-950">{{ $schedule->frequency_label }}</dd></div>
        <div><dt class="text-xs font-semibold uppercase text-zinc-500">Next Due</dt><dd class="mt-1 text-sm font-medium text-zinc-950">{{ $schedule->next_due_date->format('M d, Y') }}</dd></div>
        <div><dt class="text-xs font-semibold uppercase text-zinc-500">Last Completed</dt><dd class="mt-1 text-sm font-medium text-zinc-950">{{ $schedule->last_completed_date?->format('M d, Y') ?? 'Not recorded' }}</dd></div>
        <div><dt class="text-xs font-semibold uppercase text-zinc-500">Location</dt><dd class="mt-1 text-sm font-medium text-zinc-950">{{ $schedule->asset->location ?? 'Unassigned' }}</dd></div>
        <div><dt class="text-xs font-semibold uppercase text-zinc-500">Active</dt><dd class="mt-1 text-sm font-medium text-zinc-950">{{ $schedule->is_active ? 'Yes' : 'No' }}</dd></div>
    </dl>

    @if ($schedule->description)
        <div class="mt-5 rounded-md border border-zinc-200 bg-white p-5 text-sm leading-6 text-zinc-700 shadow-sm">{{ $schedule->description }}</div>
    @endif

    @can('complete', $schedule)
        <form method="POST" action="{{ route('maintenance-schedules.complete', $schedule) }}" class="mt-6 space-y-4 rounded-md border border-zinc-200 bg-white p-5 shadow-sm">
            @csrf
            <h2 class="text-lg font-semibold text-zinc-950">Record completion</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <label class="grid gap-2 text-sm font-medium text-zinc-700">Completion date<input type="date" name="completion_date" value="{{ old('completion_date', now()->toDateString()) }}" required class="rounded-md border-zinc-300"></label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700">Performed by<input name="performed_by" value="{{ old('performed_by') }}" class="rounded-md border-zinc-300"></label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 md:col-span-2">Actions taken<textarea name="actions_taken" rows="4" required class="rounded-md border-zinc-300">{{ old('actions_taken') }}</textarea></label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700">Findings<textarea name="findings" rows="3" class="rounded-md border-zinc-300">{{ old('findings') }}</textarea></label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700">Remarks<textarea name="remarks" rows="3" class="rounded-md border-zinc-300">{{ old('remarks') }}</textarea></label>
            </div>
            <div class="flex justify-end"><button class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Complete schedule</button></div>
        </form>
    @endcan
</x-app-layout>
