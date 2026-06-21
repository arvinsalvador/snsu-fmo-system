<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-gray-900">Assignment queue</h1></x-slot>
    <div class="space-y-4">
        @forelse ($workOrders as $workOrder)
            <article class="flex flex-col gap-5 border-b border-gray-200 bg-white p-5 shadow-sm sm:rounded-lg lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-semibold text-emerald-700">{{ $workOrder->work_order_number }}</p>
                    <h2 class="mt-1 text-lg font-semibold text-gray-900">{{ $workOrder->title }}</h2>
                    <p class="mt-2 text-sm text-gray-500">{{ $workOrder->category?->name }} · {{ $workOrder->priority?->name }} · Preferred: {{ $workOrder->preferredStaff?->user?->name ?? 'None' }}</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('work-orders.show', $workOrder) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Open</a>
                    <a href="{{ route('work-orders.recommendations', $workOrder) }}" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white">Find staff</a>
                </div>
            </article>
        @empty
            <div class="py-16 text-center text-gray-500">No approved work orders are waiting for assignment.</div>
        @endforelse
        {{ $workOrders->links() }}
    </div>
</x-app-layout>
