<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-gray-900">Approval queue</h1></x-slot>
    <div class="space-y-4">
        @forelse ($workOrders as $workOrder)
            <article class="grid gap-5 border-b border-gray-200 bg-white p-5 shadow-sm sm:rounded-lg lg:grid-cols-[1fr_320px]">
                <div>
                    <p class="text-sm font-semibold text-emerald-700">{{ $workOrder->work_order_number }}</p>
                    <a href="{{ route('work-orders.show', $workOrder) }}" class="mt-1 block text-lg font-semibold text-gray-900 hover:text-emerald-800">{{ $workOrder->title }}</a>
                    <p class="mt-2 line-clamp-2 text-sm text-gray-600">{{ $workOrder->description }}</p>
                    <p class="mt-3 text-sm text-gray-500">{{ $workOrder->requestor?->name }} · {{ $workOrder->category?->name }} · {{ $workOrder->priority?->name }}</p>
                </div>
                <div class="flex items-center justify-end gap-3">
                    <form method="POST" action="{{ route('work-orders.approve', $workOrder) }}">@csrf<button class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Approve</button></form>
                    <a href="{{ route('work-orders.show', $workOrder) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Review</a>
                </div>
            </article>
        @empty
            <div class="py-16 text-center text-gray-500">The approval queue is clear.</div>
        @endforelse
        {{ $workOrders->links() }}
    </div>
</x-app-layout>
