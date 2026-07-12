@php
    $user = Auth::user();
    $link = 'flex items-center rounded-md px-3 py-2 text-sm font-medium transition';
    $active = 'bg-emerald-50 text-emerald-800';
    $inactive = 'text-gray-600 hover:bg-gray-100 hover:text-gray-900';
@endphp

<div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-gray-900/40 md:hidden" @click="sidebarOpen = false"></div>
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-gray-200 bg-white transition-transform md:translate-x-0">
    <div class="flex h-20 items-center justify-between border-b border-gray-200 px-5">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <x-application-logo class="h-9 w-9 fill-current text-emerald-700" />
            <div><p class="font-bold text-gray-900">SNSU FMO</p><p class="text-xs text-gray-500">Work Order System</p></div>
        </a>
        <button @click="sidebarOpen = false" class="rounded-md p-2 text-gray-500 md:hidden" aria-label="Close navigation">&times;</button>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5">
        <a href="{{ route('dashboard') }}" class="{{ $link }} {{ request()->routeIs('dashboard') ? $active : $inactive }}">Dashboard</a>
        <a href="{{ route('my-requests') }}" class="{{ $link }} {{ request()->routeIs('my-requests') ? $active : $inactive }}">My requests</a>
        @can('create', App\Models\WorkOrder::class)
            <a href="{{ route('work-orders.create') }}" class="{{ $link }} {{ request()->routeIs('work-orders.create') ? $active : $inactive }}">Create request</a>
        @endcan
        @if ($user->can('manage_work_orders'))
            <a href="{{ route('work-orders.index') }}" class="{{ $link }} {{ request()->routeIs('work-orders.index') ? $active : $inactive }}">Work orders</a>
        @endif
        @if ($user->can('approve_work_orders'))
            <a href="{{ route('work-orders.approval-queue') }}" class="{{ $link }} {{ request()->routeIs('work-orders.approval-queue') ? $active : $inactive }}">Approval queue</a>
        @endif
        @if ($user->can('assign_work_orders'))
            <a href="{{ route('work-orders.assignment-queue') }}" class="{{ $link }} {{ request()->routeIs('work-orders.assignment-queue') ? $active : $inactive }}">Assignment queue</a>
        @endif
        @if ($user->hasRole('FMO Staff'))
            <a href="{{ route('work-orders.assigned-tasks') }}" class="{{ $link }} {{ request()->routeIs('work-orders.assigned-tasks') ? $active : $inactive }}">Assigned tasks</a>
        @endif
        @can('viewReports')
            <div class="pt-4"><p class="px-3 pb-2 text-xs font-semibold uppercase text-gray-400">Insights</p></div>
            <a href="{{ route('admin.reports.dashboard') }}" class="{{ $link }} {{ request()->routeIs('admin.reports.*') ? $active : $inactive }}">Reports & analytics</a>
        @endcan
        @if ($user->can('manage_users') || $user->can('manage_staff_profiles') || $user->can('manage_skills') || $user->can('manage_roles') || $user->can('manage_permissions') || $user->can('view_inventory') || $user->can('manage_inventory') || $user->can('view_assets') || $user->can('manage_assets') || $user->can('view_maintenance_records') || $user->can('view_maintenance_schedules') || $user->can('view_maintenance_reviews'))
            <div class="pt-4"><p class="px-3 pb-2 text-xs font-semibold uppercase text-gray-400">Administration</p></div>
            @can('manage_users')<a href="{{ route('admin.users.index') }}" class="{{ $link }} {{ request()->routeIs('admin.users.*') ? $active : $inactive }}">Users</a>@endcan
            @can('manage_staff_profiles')<a href="{{ route('admin.staff.index') }}" class="{{ $link }} {{ request()->routeIs('admin.staff.*') ? $active : $inactive }}">Staff profiles</a>@endcan
            @can('manage_skills')<a href="{{ route('admin.skills.index') }}" class="{{ $link }} {{ request()->routeIs('admin.skills.*') ? $active : $inactive }}">Skills</a>@endcan
            @can('manage_roles')<a href="{{ route('admin.roles.index') }}" class="{{ $link }} {{ request()->routeIs('admin.roles.*') ? $active : $inactive }}">Roles</a>@endcan
            @can('manage_permissions')<a href="{{ route('admin.permissions.index') }}" class="{{ $link }} {{ request()->routeIs('admin.permissions.*') ? $active : $inactive }}">Permissions</a>@endcan
            @if ($user->can('view_inventory') || $user->can('manage_inventory'))<a href="{{ route('admin.inventory.index') }}" class="{{ $link }} {{ request()->routeIs('admin.inventory.*') ? $active : $inactive }}">Inventory</a>@endif
            @if ($user->can('view_inventory') || $user->can('manage_inventory'))<a href="{{ route('admin.inventory-intelligence.dashboard') }}" class="{{ $link }} {{ request()->routeIs('admin.inventory-intelligence.*') ? $active : $inactive }}">Inventory intelligence</a>@endif
            @if ($user->can('view_assets') || $user->can('manage_assets'))<a href="{{ route('admin.assets.index') }}" class="{{ $link }} {{ request()->routeIs('admin.assets.*') ? $active : $inactive }}">Assets</a>@endif
            @if ($user->can('view_maintenance_records') || $user->can('manage_maintenance_records'))<a href="{{ route('admin.asset-maintenance.index') }}" class="{{ $link }} {{ request()->routeIs('admin.asset-maintenance.*') ? $active : $inactive }}">Maintenance history</a>@endif
            @if ($user->can('view_maintenance_schedules') || $user->can('manage_maintenance_schedules'))<a href="{{ route('maintenance-schedules.index') }}" class="{{ $link }} {{ request()->routeIs('maintenance-schedules.*') ? $active : $inactive }}">Maintenance schedules</a>@endif
            @can('view_maintenance_reviews')<a href="{{ route('admin.maintenance-reviews.index') }}" class="{{ $link }} {{ request()->routeIs('admin.maintenance-reviews.*') ? $active : $inactive }}">Maintenance reviews</a>@endcan
        @endif
        @if ($user->can('manage_master_data') || $user->can('manage_locations') || $user->can('manage_work_order_settings'))
            <div class="pt-4"><p class="px-3 pb-2 text-xs font-semibold uppercase text-gray-400">Master data</p></div>
            <a href="{{ $user->can('manage_master_data') || $user->can('manage_locations') ? route('admin.master-data.buildings.index') : route('admin.master-data.work-order-categories.index') }}" class="{{ $link }} {{ request()->routeIs('admin.master-data.*') ? $active : $inactive }}">Manage master data</a>
        @endif
    </nav>

    <div class="border-t border-gray-200 p-4">
        <p class="truncate text-sm font-semibold text-gray-900">{{ $user->name }}</p>
        <p class="truncate text-xs text-gray-500">{{ $user->getRoleNames()->first() ?? 'User' }}</p>
        <div class="mt-3 flex items-center gap-3 text-sm">
            <a href="{{ route('profile.edit') }}" class="font-medium text-gray-600 hover:text-gray-900">Profile</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="font-medium text-red-600 hover:text-red-800">Log out</button></form>
        </div>
    </div>
</aside>
