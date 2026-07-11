<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold tracking-tight">New Maintenance Schedule</h1></x-slot>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight">New Maintenance Schedule</h1>
        <p class="mt-1 text-sm text-zinc-600">Create a preventive maintenance cycle linked to an asset.</p>
    </div>

    <form method="POST" action="{{ route('maintenance-schedules.store') }}" class="rounded-md border border-zinc-200 bg-white p-5 shadow-sm">
        @include('maintenance-schedules._form')
    </form>
</x-app-layout>
