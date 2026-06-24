@csrf

<div class="grid gap-5 md:grid-cols-2">
    <label class="grid gap-2 text-sm font-medium text-zinc-700">
        Asset
        <select name="asset_id" required class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-zinc-950 shadow-sm focus:border-zinc-950 focus:outline-none">
            <option value="">Select asset</option>
            @foreach ($assets as $asset)
                <option value="{{ $asset->id }}" @selected((string) old('asset_id', $schedule->asset_id) === (string) $asset->id)>{{ $asset->asset_tag }} - {{ $asset->name }}</option>
            @endforeach
        </select>
        @error('asset_id') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="grid gap-2 text-sm font-medium text-zinc-700">
        Schedule title
        <input name="title" value="{{ old('title', $schedule->title) }}" required maxlength="255" class="rounded-md border border-zinc-300 px-3 py-2 text-zinc-950 shadow-sm focus:border-zinc-950 focus:outline-none">
        @error('title') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="grid gap-2 text-sm font-medium text-zinc-700">
        Frequency
        <select name="frequency" required class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-zinc-950 shadow-sm focus:border-zinc-950 focus:outline-none">
            @foreach (array_keys($frequencies) as $frequency)
                <option value="{{ $frequency }}" @selected(old('frequency', $schedule->frequency) === $frequency)>{{ str($frequency)->title() }}</option>
            @endforeach
        </select>
        @error('frequency') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="grid gap-2 text-sm font-medium text-zinc-700">
        Next due date
        <input type="date" name="next_due_date" value="{{ old('next_due_date', optional($schedule->next_due_date)->format('Y-m-d')) }}" required class="rounded-md border border-zinc-300 px-3 py-2 text-zinc-950 shadow-sm focus:border-zinc-950 focus:outline-none">
        @error('next_due_date') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="grid gap-2 text-sm font-medium text-zinc-700">
        Last completed date
        <input type="date" name="last_completed_date" value="{{ old('last_completed_date', optional($schedule->last_completed_date)->format('Y-m-d')) }}" class="rounded-md border border-zinc-300 px-3 py-2 text-zinc-950 shadow-sm focus:border-zinc-950 focus:outline-none">
        @error('last_completed_date') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="flex items-center gap-3 self-end rounded-md border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm font-medium text-zinc-700">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $schedule->is_active)) class="h-4 w-4 rounded border-zinc-300 text-zinc-950 focus:ring-zinc-950">
        Active schedule
    </label>
</div>

<label class="mt-5 grid gap-2 text-sm font-medium text-zinc-700">
    Description
    <textarea name="description" rows="4" class="rounded-md border border-zinc-300 px-3 py-2 text-zinc-950 shadow-sm focus:border-zinc-950 focus:outline-none">{{ old('description', $schedule->description) }}</textarea>
    @error('description') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
</label>

<div class="mt-6 flex items-center justify-end gap-3">
    <a href="{{ route('maintenance-schedules.index') }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100">Cancel</a>
    <button class="rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800">Save Schedule</button>
</div>
