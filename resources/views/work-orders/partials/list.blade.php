<div class="overflow-hidden border border-gray-200 bg-white shadow-sm sm:rounded-lg">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-5 py-3">Work order</th>
                    <th class="px-5 py-3">Location</th>
                    <th class="px-5 py-3">Priority</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Requested</th>
                    <th class="px-5 py-3"><span class="sr-only">Open</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($workOrders as $workOrder)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-4">
                            <div class="font-semibold text-gray-900">{{ $workOrder->work_order_number }}</div>
                            <div class="mt-1 max-w-md text-gray-600">{{ $workOrder->title }}</div>
                        </td>
                        <td class="px-5 py-4 text-gray-600">
                            {{ $workOrder->building?->name ?? 'Not set' }}
                            @if ($workOrder->room)<div class="text-xs text-gray-500">{{ $workOrder->room->room_name }}</div>@endif
                        </td>
                        <td class="px-5 py-4 text-gray-700">{{ $workOrder->priority?->name ?? 'Not set' }}</td>
                        <td class="px-5 py-4">
                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">{{ $workOrder->status?->name ?? 'Unknown' }}</span>
                        </td>
                        <td class="px-5 py-4 text-gray-600">{{ $workOrder->requested_at?->format('M j, Y') }}</td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('work-orders.show', $workOrder) }}" class="font-semibold text-emerald-700 hover:text-emerald-900">Open</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-gray-500">No work orders found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($workOrders instanceof \Illuminate\Contracts\Pagination\Paginator)
    <div class="mt-5">{{ $workOrders->withQueryString()->links() }}</div>
@endif
