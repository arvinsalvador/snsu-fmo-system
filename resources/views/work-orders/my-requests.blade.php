<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold text-gray-900">My requests</h1>
            <a href="{{ route('work-orders.create') }}" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">New request</a>
        </div>
    </x-slot>
    @include('work-orders.partials.list')
</x-app-layout>
