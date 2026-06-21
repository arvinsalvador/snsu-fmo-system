<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-medium text-emerald-700">Administration / Skills</p><h1 class="text-2xl font-semibold text-gray-900">{{ $skill ? 'Edit skill' : 'Add skill' }}</h1></div></x-slot>
    <div class="mx-auto max-w-2xl space-y-5">@include('admin.partials.tabs')<form method="POST" action="{{ $skill ? route('admin.skills.update', $skill) : route('admin.skills.store') }}" class="space-y-5 rounded-md border border-gray-200 bg-white p-6">@csrf @if($skill)@method('PUT')@endif
        <div><label for="name" class="mb-1 block text-sm font-medium">Name</label><input id="name" name="name" value="{{ old('name', $skill?->name) }}" class="w-full rounded-md border-gray-300 text-sm">@error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
        <div><label for="description" class="mb-1 block text-sm font-medium">Description</label><textarea id="description" name="description" rows="4" class="w-full rounded-md border-gray-300 text-sm">{{ old('description', $skill?->description) }}</textarea></div>
        <input type="hidden" name="is_active" value="0"><label class="flex items-center gap-2 text-sm font-medium"><input type="checkbox" name="is_active" value="1" @checked((bool)old('is_active', $skill?->is_active ?? true)) class="rounded border-gray-300 text-emerald-700">Active</label>
        <div class="flex justify-end gap-3 border-t border-gray-200 pt-5"><a href="{{ route('admin.skills.index') }}" class="px-3 py-2 text-sm font-semibold text-gray-600">Cancel</a><button class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">{{ $skill ? 'Save changes' : 'Create skill' }}</button></div>
    </form></div>
</x-app-layout>
