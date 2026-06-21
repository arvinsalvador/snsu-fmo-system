<x-app-layout>
    <x-slot name="header">
        <div><p class="text-sm font-medium text-emerald-700">{{ $workOrder->work_order_number }}</p><h1 class="text-2xl font-semibold text-gray-900">Assignment recommendations</h1></div>
    </x-slot>
    <div class="space-y-4">
        @foreach ($recommendations as $recommendation)
            @php($profile = $recommendation['staff_profile'])
            <article class="grid gap-5 border-b border-gray-200 bg-white p-5 shadow-sm sm:rounded-lg lg:grid-cols-[1fr_auto] lg:items-center">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-semibold text-gray-900">{{ $profile->user?->name }}</h2>
                        @if ($recommendation['is_preferred_staff'])<span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-800">Preferred</span>@endif
                        <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">Score {{ $recommendation['recommendation_score'] }}</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-600">{{ $profile->designation }} · {{ ucfirst($profile->availability_status) }}</p>
                    <p class="mt-3 text-sm text-gray-600">Active {{ $profile->active_assigned_count }} · Pending {{ $profile->pending_assigned_count }} · In progress {{ $profile->in_progress_assigned_count }}</p>
                    <p class="mt-2 text-sm text-gray-500">Matched skills: {{ $recommendation['matched_skills']->pluck('name')->join(', ') ?: 'None' }}</p>
                </div>
                @can('assign', $workOrder)
                    @if ($workOrder->activeAssignments->isEmpty())
                        <form method="POST" action="{{ route('work-orders.assign', $workOrder) }}">@csrf<input type="hidden" name="assigned_staff_id" value="{{ $profile->id }}"><button class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Assign</button></form>
                    @else
                        <form method="POST" action="{{ route('work-orders.reassign', $workOrder) }}">@csrf<input type="hidden" name="assignment_type" value="individual"><input type="hidden" name="assigned_staff_id" value="{{ $profile->id }}"><input type="hidden" name="remarks" value="Reassigned from web dashboard"><button class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-800">Reassign</button></form>
                    @endif
                @endcan
            </article>
        @endforeach
    </div>
</x-app-layout>
