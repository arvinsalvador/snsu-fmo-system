<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium text-emerald-700">{{ $role }}</p>
            <h1 class="text-2xl font-semibold text-gray-900">Work order dashboard</h1>
        </div>
    </x-slot>

    <div class="space-y-8">
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ([['Open records', $total], ['Pending approval', $pending_approval], ['Active work', $active], ['Completed', $completed], ['Assigned to me', $assigned_to_me]] as [$label, $value])
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">{{ $label }}</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ number_format($value) }}</p>
                </div>
            @endforeach
        </section>

        <section>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Recent work orders</h2>
                @can('create', App\Models\WorkOrder::class)
                    <a href="{{ route('work-orders.create') }}" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">New request</a>
                @endcan
            </div>
            @include('work-orders.partials.list', ['workOrders' => $recent])
        </section>
    </div>
</x-app-layout>
