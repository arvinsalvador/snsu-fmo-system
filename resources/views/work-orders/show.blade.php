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
                    @foreach ($timeline as $event)
                        <li class="relative">
                            <span class="absolute -left-[31px] top-1 h-3 w-3 rounded-full ring-4 ring-white {{ $event['type'] === 'followup' ? 'bg-blue-600' : ($event['type'] === 'approval' ? 'bg-amber-500' : 'bg-emerald-600') }}"></span>
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div><p class="font-semibold text-gray-900">{{ $event['title'] }}</p><p class="text-sm text-gray-500">{{ $event['actor'] ?: 'System' }} · {{ $event['occurred_at']?->format('M j, Y g:i A') }}</p></div>
                                @if($event['meta'] ?? null)<span class="text-sm text-gray-600">{{ $event['meta'] }}</span>@endif
                            </div>
                            @if($event['body'])<p class="mt-3 whitespace-pre-line text-gray-700">{{ $event['body'] }}</p>@endif
                        </li>
                    @endforeach
                </ol>
            </section>

            @can('viewFollowups', $workOrder)
                <section class="border-t border-gray-200 pt-7">
                    <div class="mb-4"><h2 class="text-lg font-semibold text-gray-900">Follow-up thread</h2><p class="mt-1 text-sm text-gray-500">Messages remain attached to this work order and cannot be deleted.</p></div>
                    <div class="divide-y divide-gray-100 border-y border-gray-200">
                        @forelse($workOrder->followups as $followup)
                            <article class="py-4"><div class="flex flex-wrap items-center justify-between gap-2"><p class="text-sm font-semibold text-gray-900">{{ $followup->user?->name }}</p><time class="text-xs text-gray-500">{{ $followup->created_at->format('M j, Y g:i A') }}</time></div><p class="mt-2 whitespace-pre-line text-gray-700">{{ $followup->message }}</p></article>
                        @empty
                            <p class="py-6 text-center text-sm text-gray-500">No follow-up messages yet.</p>
                        @endforelse
                    </div>
                    @can('createFollowup', $workOrder)
                        <form method="POST" action="{{ route('work-orders.followups.store', $workOrder) }}" class="mt-5 space-y-3">@csrf<label for="followup-message" class="block text-sm font-semibold text-gray-900">Add follow-up</label><textarea id="followup-message" name="message" rows="4" maxlength="5000" required placeholder="Write a question or response" class="w-full rounded-md border-gray-300 text-sm">{{ old('message') }}</textarea>@error('message')<p class="text-sm text-red-600">{{ $message }}</p>@enderror<div class="flex justify-end"><button class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Send message</button></div></form>
                    @endcan
                </section>
            @endcan

            @can('viewForWorkOrder', [App\Models\WorkOrderEvaluation::class, $workOrder])
                @if ($workOrder->evaluation)
                    <section class="border-t border-gray-200 pt-7">
                        <h2 class="text-lg font-semibold text-gray-900">Service evaluation</h2>
                        <div class="mt-4 border-y border-gray-200 py-5">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-3" aria-label="{{ $workOrder->evaluation->rating }} out of 5 stars">
                                    <span class="text-2xl font-semibold text-gray-900">{{ $workOrder->evaluation->rating }}/5</span>
                                    <span class="text-lg text-amber-500">{{ str_repeat('★', $workOrder->evaluation->rating) }}<span class="text-gray-300">{{ str_repeat('★', 5 - $workOrder->evaluation->rating) }}</span></span>
                                </div>
                                <time class="text-sm text-gray-500">{{ $workOrder->evaluation->evaluated_at->format('M j, Y g:i A') }}</time>
                            </div>
                            @if ($workOrder->evaluation->comments)
                                <p class="mt-4 whitespace-pre-line text-gray-700">{{ $workOrder->evaluation->comments }}</p>
                            @endif
                        </div>
                    </section>
                @elseif ($workOrder->status?->name === 'Completed')
                    @can('create', [App\Models\WorkOrderEvaluation::class, $workOrder])
                        <section class="border-t border-gray-200 pt-7">
                            <h2 class="text-lg font-semibold text-gray-900">Evaluate completed work</h2>
                            <form method="POST" action="{{ route('work-orders.evaluation.store', $workOrder) }}" class="mt-5 space-y-5">
                                @csrf
                                <fieldset>
                                    <legend class="text-sm font-semibold text-gray-900">Overall rating</legend>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach (range(1, 5) as $rating)
                                            <label class="cursor-pointer">
                                                <input type="radio" name="rating" value="{{ $rating }}" required class="peer sr-only" @checked((int) old('rating') === $rating)>
                                                <span class="flex h-11 w-11 items-center justify-center rounded-md border border-gray-300 text-sm font-semibold text-gray-700 peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-800">{{ $rating }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('rating')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                                </fieldset>
                                <div>
                                    <label for="evaluation-comments" class="block text-sm font-semibold text-gray-900">Comments <span class="font-normal text-gray-500">(optional)</span></label>
                                    <textarea id="evaluation-comments" name="comments" rows="4" maxlength="5000" class="mt-2 w-full rounded-md border-gray-300 text-sm" placeholder="Share feedback about the completed work">{{ old('comments') }}</textarea>
                                    @error('comments')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div class="flex justify-end"><button class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Submit evaluation</button></div>
                            </form>
                        </section>
                    @endcan
                @endif
            @endcan
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
