<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ $workOrder->work_order_number }}</p>
                <h1 class="text-2xl font-semibold text-gray-900">{{ $workOrder->title }}</h1>
            </div>
            @can('createUpdate', $workOrder)
                <a href="{{ route('work-orders.progress.create', $workOrder) }}" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Add progress</a>
            @endcan
        </div>
    </x-slot>

    <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_320px]">
        <div class="space-y-9">
            <section class="border-b border-gray-200 pb-8">
                <div class="mb-5 flex flex-wrap gap-2">
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-sm font-semibold text-gray-700">{{ $workOrder->status?->name }}</span>
                    <span class="rounded-full bg-amber-50 px-3 py-1 text-sm font-semibold text-amber-800">{{ $workOrder->priority?->name }}</span>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-800">{{ ucfirst($workOrder->approval_status) }}</span>
                </div>
                <p class="whitespace-pre-line leading-7 text-gray-700">{{ $workOrder->description }}</p>
            </section>

            <section>
                <h2 class="mb-5 text-lg font-semibold text-gray-900">Progress timeline</h2>
                <ol class="space-y-6 border-l-2 border-gray-200 pl-6">
                    @forelse ($workOrder->updates->sortBy('created_at') as $update)
                        <li class="relative">
                            <span class="absolute -left-[31px] top-1 h-3 w-3 rounded-full bg-emerald-600 ring-4 ring-white"></span>
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $update->status?->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $update->creator?->name }} · {{ $update->created_at->format('M j, Y g:i A') }}</p>
                                </div>
                                @if (! is_null($update->estimated_remaining_days))<span class="text-sm text-gray-600">{{ $update->estimated_remaining_days }} day(s) remaining</span>@endif
                            </div>
                            <p class="mt-3 whitespace-pre-line text-gray-700">{{ $update->notes }}</p>
                            @if ($update->photos->isNotEmpty())<p class="mt-2 text-sm text-gray-500">{{ $update->photos->count() }} photo attachment(s)</p>@endif
                        </li>
                    @empty
                        <li class="text-gray-500">No progress updates have been recorded.</li>
                    @endforelse
                </ol>
            </section>

            @if ($workOrder->assignments->isNotEmpty())
                <section class="border-t border-gray-200 pt-7">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">Assignment history</h2>
                    <div class="divide-y divide-gray-100 border-y border-gray-200">
                        @foreach ($workOrder->assignments->sortBy('assigned_at') as $assignment)
                            <div class="flex flex-wrap justify-between gap-3 py-4 text-sm">
                                <div><p class="font-semibold text-gray-900">{{ $assignment->assignedStaff?->user?->name }}</p><p class="text-gray-500">{{ ucfirst($assignment->assignment_type) }}</p></div>
                                <div class="text-right text-gray-600"><p>{{ $assignment->assigned_at->format('M j, Y') }}</p><p>{{ $assignment->unassigned_at ? 'Reassigned' : 'Active' }}</p></div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        <aside class="space-y-7">
            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-gray-900">Request details</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="text-gray-500">Requestor</dt><dd class="font-medium text-gray-900">{{ $workOrder->requestor?->name }}</dd></div>
                    <div><dt class="text-gray-500">Category</dt><dd class="font-medium text-gray-900">{{ $workOrder->category?->name }}</dd></div>
                    <div><dt class="text-gray-500">Department</dt><dd class="font-medium text-gray-900">{{ $workOrder->department?->name ?? 'Not set' }}</dd></div>
                    <div><dt class="text-gray-500">Location</dt><dd class="font-medium text-gray-900">{{ $workOrder->building?->name }}{{ $workOrder->room ? ' / '.$workOrder->room->room_name : '' }}</dd></div>
                    <div><dt class="text-gray-500">Requested</dt><dd class="font-medium text-gray-900">{{ $workOrder->requested_at?->format('M j, Y g:i A') }}</dd></div>
                    <div><dt class="text-gray-500">Preferred staff</dt><dd class="font-medium text-gray-900">{{ $workOrder->preferredStaff?->user?->name ?? 'No preference' }}</dd></div>
                </dl>
            </section>

            @can('approve', $workOrder)
                @if ($workOrder->approval_status === 'pending')
                    <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <h2 class="font-semibold text-gray-900">Approval action</h2>
                        <form method="POST" action="{{ route('work-orders.approve', $workOrder) }}" class="mt-4 space-y-3">@csrf<textarea name="remarks" rows="3" placeholder="Approval remarks (optional)" class="w-full rounded-md border-gray-300 text-sm"></textarea><button class="w-full rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Approve</button></form>
                        <form method="POST" action="{{ route('work-orders.reject', $workOrder) }}" class="mt-3 space-y-3">@csrf<textarea name="remarks" rows="3" required placeholder="Rejection reason" class="w-full rounded-md border-gray-300 text-sm"></textarea><button class="w-full rounded-md border border-red-300 px-4 py-2 text-sm font-semibold text-red-700">Reject</button></form>
                    </section>
                @endif
            @endcan

            @can('viewAssignmentRecommendations', $workOrder)
                <a href="{{ route('work-orders.recommendations', $workOrder) }}" class="block rounded-md border border-gray-300 px-4 py-3 text-center text-sm font-semibold text-gray-800 hover:bg-gray-50">Assignment recommendations</a>
            @endcan
        </aside>
    </div>
</x-app-layout>
