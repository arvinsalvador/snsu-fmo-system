<x-app-layout>
    <x-slot name="header">
        <div><p class="text-sm font-medium text-emerald-700">{{ $workOrder->work_order_number }}</p><h1 class="text-2xl font-semibold text-gray-900">Add progress update</h1></div>
    </x-slot>
    <form method="POST" action="{{ route('work-orders.progress.store', $workOrder) }}" class="max-w-2xl space-y-6">
        @csrf
        <div>
            <label for="status_id" class="text-sm font-medium text-gray-700">Status</label>
            <select id="status_id" name="status_id" class="mt-1 w-full rounded-md border-gray-300">
                <option value="">Automatic (first update starts work)</option>
                @foreach ($progressStatuses as $status)<option value="{{ $status->id }}" @selected(old('status_id') == $status->id)>{{ $status->name }}</option>@endforeach
            </select>
            <x-input-error :messages="$errors->get('status_id')" class="mt-2" />
        </div>
        <div>
            <label for="notes" class="text-sm font-medium text-gray-700">Work completed today</label>
            <textarea id="notes" name="notes" rows="7" required class="mt-1 w-full rounded-md border-gray-300">{{ old('notes') }}</textarea>
            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
        </div>
        <div>
            <label for="estimated_remaining_days" class="text-sm font-medium text-gray-700">Estimated remaining days</label>
            <input id="estimated_remaining_days" type="number" min="0" max="3650" name="estimated_remaining_days" value="{{ old('estimated_remaining_days') }}" class="mt-1 w-full rounded-md border-gray-300 sm:max-w-xs">
        </div>
        <div class="flex justify-end gap-3 border-t border-gray-200 pt-5">
            <a href="{{ route('work-orders.show', $workOrder) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Cancel</a>
            <button class="rounded-md bg-emerald-700 px-5 py-2 text-sm font-semibold text-white">Save update</button>
        </div>
    </form>
</x-app-layout>
