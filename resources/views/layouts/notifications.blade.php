@can('viewAny', Illuminate\Notifications\DatabaseNotification::class)
    @php
        $unreadNotificationCount = Auth::user()->unreadNotifications()->count();
        $recentNotifications = Auth::user()->notifications()->latest()->limit(8)->get();
    @endphp
    <div class="relative" @click.outside="notificationOpen = false">
        <button type="button" @click="notificationOpen = !notificationOpen" class="relative rounded-md p-2 text-gray-600 hover:bg-gray-100" aria-label="Notifications" :aria-expanded="notificationOpen">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 01-6 0" /></svg>
            @if($unreadNotificationCount)<span class="absolute -right-1 -top-1 min-w-5 rounded-full bg-red-600 px-1 text-center text-xs font-semibold leading-5 text-white">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>@endif
        </button>
        <div x-cloak x-show="notificationOpen" x-transition class="absolute right-0 z-50 mt-2 w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-md border border-gray-200 bg-white shadow-lg">
            <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3"><h2 class="text-sm font-semibold text-gray-900">Notifications</h2>@if($unreadNotificationCount)<form method="POST" action="{{ route('notifications.read-all') }}">@csrf @method('PATCH')<button class="text-xs font-semibold text-emerald-700">Mark all read</button></form>@endif</div>
            <div class="max-h-96 divide-y divide-gray-100 overflow-y-auto">
                @forelse($recentNotifications as $notification)
                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">@csrf @method('PATCH')<button class="block w-full px-4 py-3 text-left hover:bg-gray-50"><span class="flex items-start gap-3"><span class="mt-1 h-2 w-2 shrink-0 rounded-full {{ $notification->read_at ? 'bg-gray-300' : 'bg-emerald-600' }}"></span><span class="min-w-0"><span class="block text-sm font-medium text-gray-900">{{ $notification->data['message'] ?? 'Work order notification' }}</span><span class="mt-1 block text-xs text-gray-500">{{ $notification->created_at->diffForHumans() }}</span></span></span></button></form>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-gray-500">No notifications yet.</p>
                @endforelse
            </div>
        </div>
    </div>
@endcan
