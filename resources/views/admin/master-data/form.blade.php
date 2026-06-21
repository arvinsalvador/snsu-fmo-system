<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-medium text-emerald-700">{{ $module['label'] }}</p><h1 class="text-2xl font-semibold text-gray-900">{{ $record ? 'Edit record' : 'Add record' }}</h1></div></x-slot>
    <div class="mx-auto max-w-3xl space-y-5">
        @include('admin.master-data.partials.tabs')
        <form method="POST" action="{{ $record ? route("admin.master-data.{$moduleKey}.update", $record) : route("admin.master-data.{$moduleKey}.store") }}" class="space-y-6 rounded-md border border-gray-200 bg-white p-5 sm:p-7">
            @csrf @if ($record) @method('PUT') @endif
            <div class="grid gap-5 sm:grid-cols-2">
                @foreach ($module['fields'] as $field)
                    @php $name = $field['name']; $value = old($name, $record?->{$name}); @endphp
                    <div class="{{ $field['type'] === 'textarea' ? 'sm:col-span-2' : '' }}">
                        @if ($field['type'] === 'checkbox')
                            <input type="hidden" name="{{ $name }}" value="0"><label class="flex items-center gap-3 text-sm font-medium text-gray-700"><input type="checkbox" name="{{ $name }}" value="1" @checked((bool) $value) class="rounded border-gray-300 text-emerald-700 focus:ring-emerald-600">{{ $field['label'] }}</label>
                        @else
                            <label for="{{ $name }}" class="mb-1 block text-sm font-medium text-gray-700">{{ $field['label'] }}</label>
                            @if ($field['type'] === 'textarea')
                                <textarea id="{{ $name }}" name="{{ $name }}" rows="4" class="w-full rounded-md border-gray-300 text-sm">{{ $value }}</textarea>
                            @elseif ($field['type'] === 'select')
                                <select id="{{ $name }}" name="{{ $name }}" class="w-full rounded-md border-gray-300 text-sm"><option value="">Select {{ strtolower($field['label']) }}</option>@foreach ($options[$field['options']] as $option)<option value="{{ $option->id }}" @selected((string) $value === (string) $option->id)>{{ $field['options'] === 'floors' ? ($option->building?->name.' · '.$option->floor_name) : $option->name }}</option>@endforeach</select>
                            @else
                                <input id="{{ $name }}" type="{{ $field['type'] }}" name="{{ $name }}" value="{{ $value }}" class="w-full rounded-md border-gray-300 text-sm" @if ($field['type'] === 'number') min="1" @endif>
                            @endif
                        @endif
                        @error($name)<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endforeach
                <div class="sm:col-span-2"><input type="hidden" name="is_active" value="0"><label class="flex items-center gap-3 text-sm font-medium text-gray-700"><input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $record?->is_active ?? true)) class="rounded border-gray-300 text-emerald-700 focus:ring-emerald-600">Active</label></div>
            </div>
            <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-5"><a href="{{ route("admin.master-data.{$moduleKey}.index") }}" class="px-3 py-2 text-sm font-semibold text-gray-600">Cancel</a><button class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">{{ $record ? 'Save changes' : 'Create record' }}</button></div>
        </form>
    </div>
</x-app-layout>
