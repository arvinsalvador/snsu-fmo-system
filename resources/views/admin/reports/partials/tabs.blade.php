@php
    $tabs = [
        'work-orders' => ['Work orders', 'viewWorkOrderReports'],
        'assets' => ['Assets', 'viewAssetReports'],
        'maintenance-schedules' => ['PM schedules', 'viewMaintenanceReports'],
        'maintenance-records' => ['Maintenance', 'viewMaintenanceReports'],
        'inventory' => ['Inventory', 'viewInventoryReports'],
        'staff' => ['Staff', 'viewStaffReports'],
    ];
@endphp

<nav class="mb-6 flex gap-2 overflow-x-auto border-b border-gray-200 pb-2" aria-label="Report categories">
    <a href="{{ route('admin.reports.dashboard', request()->query()) }}" class="whitespace-nowrap rounded-md px-3 py-2 text-sm font-semibold {{ request()->routeIs('admin.reports.dashboard') ? 'bg-emerald-700 text-white' : 'text-gray-600 hover:bg-gray-100' }}">Overview</a>
    @foreach ($tabs as $slug => [$label, $ability])
        @can($ability)
            <a href="{{ route('admin.reports.show', ['report' => $slug, ...request()->query()]) }}" class="whitespace-nowrap rounded-md px-3 py-2 text-sm font-semibold {{ ($report ?? null) === $slug ? 'bg-emerald-700 text-white' : 'text-gray-600 hover:bg-gray-100' }}">{{ $label }}</a>
        @endcan
    @endforeach
    @can('generate_reports')<a href="{{ route('admin.reports.generate.form') }}" class="whitespace-nowrap rounded-md px-3 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100">Generate</a>@endcan
    @can('view_generated_reports')<a href="{{ route('admin.reports.generated.index') }}" class="whitespace-nowrap rounded-md px-3 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100">History</a>@endcan
    @can('view_report_schedules')<a href="{{ route('admin.reports.schedules.index') }}" class="whitespace-nowrap rounded-md px-3 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100">Schedules</a>@endcan
</nav>
