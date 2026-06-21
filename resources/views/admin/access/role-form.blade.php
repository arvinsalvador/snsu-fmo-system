<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-medium text-emerald-700">Administration / Access / Roles</p><h1 class="text-2xl font-semibold text-gray-900">{{ $role ? 'Edit role' : 'Add role' }}</h1></div></x-slot>
    <div class="mx-auto max-w-4xl space-y-5">@include('admin.partials.tabs')
        <form method="POST" action="{{ $role ? route('admin.roles.update', $role) : route('admin.roles.store') }}" class="space-y-6 rounded-md border border-gray-200 bg-white p-6">@csrf @if($role)@method('PUT')@endif
            @if($role?->name === 'Super Admin')<div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">The Super Admin role is system-protected and cannot be changed.</div>@endif
            <div><label for="name" class="mb-1 block text-sm font-medium">Role name</label><input id="name" name="name" value="{{ old('name', $role?->name) }}" @disabled($role?->name === 'Super Admin') class="w-full rounded-md border-gray-300 text-sm">@error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <input type="hidden" name="permissions_present" value="1"><fieldset @disabled($role?->name === 'Super Admin')><legend class="mb-3 text-sm font-semibold">Permissions</legend><div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">@foreach($permissions as $permission)<label class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm"><input type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked(in_array($permission->name, old('permissions', $role?->permissions->pluck('name')->all() ?? []), true)) class="rounded border-gray-300 text-emerald-700"><span>{{ Str::headline($permission->name) }}</span></label>@endforeach</div></fieldset>
            <div class="flex justify-end gap-3 border-t border-gray-200 pt-5"><a href="{{ route('admin.roles.index') }}" class="px-3 py-2 text-sm font-semibold text-gray-600">Cancel</a>@if($role?->name !== 'Super Admin')<button class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">{{ $role ? 'Save changes' : 'Create role' }}</button>@endif</div>
        </form>
    </div>
</x-app-layout>
