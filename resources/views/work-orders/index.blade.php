<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-gray-900">Work orders</h1></x-slot>
    <div class="space-y-5">
        <form method="GET" class="flex flex-col gap-3 border-b border-gray-200 pb-5 sm:flex-row">
            <input name="search" value="{{ request('search') }}" placeholder="Search number, title, or description" class="w-full rounded-md border-gray-300 sm:max-w-md">
            <select name="approval_status" class="rounded-md border-gray-300">
                <option value="">All approval states</option>
                @foreach (['pending', 'approved', 'rejected'] as $state)<option value="{{ $state }}" @selected(request('approval_status') === $state)>{{ ucfirst($state) }}</option>@endforeach
            </select>
            <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white">Filter</button>
        </form>
        @include('work-orders.partials.list')
    </div>
</x-app-layout>
