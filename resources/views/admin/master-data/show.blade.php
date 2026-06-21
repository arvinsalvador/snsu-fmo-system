<x-app-layout>
    <x-slot name="header"><div class="flex w-full items-center justify-between gap-4"><div><p class="text-sm font-medium text-emerald-700">{{ $module['label'] }}</p><h1 class="text-2xl font-semibold text-gray-900">Record details</h1></div><a href="{{ route("admin.master-data.{$moduleKey}.edit", $record) }}" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Edit</a></div></x-slot>
    <div class="mx-auto max-w-4xl space-y-5">
        @if (session('success'))<div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>@endif
        @include('admin.master-data.partials.tabs')
        <div class="rounded-md border border-gray-200 bg-white">
            <dl class="grid sm:grid-cols-2">
                @foreach ($module['columns'] as $key => $label)<div class="border-b border-gray-100 px-5 py-4"><dt class="text-xs font-semibold uppercase text-gray-500">{{ $label }}</dt><dd class="mt-1 text-sm font-medium text-gray-900">{{ $web->displayValue($record, $key) }}</dd></div>@endforeach
                <div class="border-b border-gray-100 px-5 py-4"><dt class="text-xs font-semibold uppercase text-gray-500">Status</dt><dd class="mt-1 text-sm font-medium text-gray-900">{{ $record->is_active ? 'Active' : 'Inactive' }}</dd></div>
                <div class="border-b border-gray-100 px-5 py-4"><dt class="text-xs font-semibold uppercase text-gray-500">Created</dt><dd class="mt-1 text-sm font-medium text-gray-900">{{ $record->created_at?->format('M j, Y g:i A') }}</dd></div>
                @if ($record->description)<div class="border-b border-gray-100 px-5 py-4 sm:col-span-2"><dt class="text-xs font-semibold uppercase text-gray-500">Description</dt><dd class="mt-1 whitespace-pre-line text-sm text-gray-700">{{ $record->description }}</dd></div>@endif
            </dl>
            <div class="flex items-center justify-between px-5 py-4"><a href="{{ route("admin.master-data.{$moduleKey}.index") }}" class="text-sm font-semibold text-gray-600">Back to list</a><form method="POST" action="{{ route("admin.master-data.{$moduleKey}.destroy", $record) }}" onsubmit="return confirm('Delete this record? Historical references will be preserved.');">@csrf @method('DELETE')<button class="text-sm font-semibold text-red-600 hover:text-red-800">Delete record</button></form></div>
        </div>
    </div>
</x-app-layout>
