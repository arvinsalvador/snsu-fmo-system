<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-medium text-emerald-700">Management insights</p><h1 class="text-2xl font-semibold text-gray-900">Reporting & analytics</h1><p class="mt-1 text-sm text-gray-500">SNSU Del Carmen Campus · {{ $filters['date_from'] }} to {{ $filters['date_to'] }}</p></div></x-slot>

    @include('admin.reports.partials.tabs')
    @include('admin.reports.partials.filters')

    @php
        $cards = [
            'total_assets' => ['Total assets', 'assets'], 'active_assets' => ['Active assets', 'assets'], 'assets_under_maintenance' => ['Under maintenance', 'assets'], 'defective_assets' => ['Defective assets', 'assets'],
            'total_work_orders' => ['Work orders', 'work-orders'], 'open_work_orders' => ['Open work orders', 'work-orders'], 'in_progress_work_orders' => ['In progress', 'work-orders'], 'completed_work_orders' => ['Completed', 'work-orders'], 'overdue_work_orders' => ['Overdue', 'work-orders'],
            'preventive_maintenance_due' => ['PM due', 'maintenance-schedules'], 'preventive_maintenance_overdue' => ['PM overdue', 'maintenance-schedules'], 'pending_maintenance_reviews' => ['Pending reviews', 'maintenance-records'], 'correction_requests' => ['Corrections', 'maintenance-records'],
            'low_stock_items' => ['Low stock', 'inventory'], 'out_of_stock_items' => ['Out of stock', 'inventory'], 'maintenance_cost' => ['Approved maintenance cost', 'maintenance-records'],
        ];
    @endphp
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($cards as $key => [$label, $target])
            <a href="{{ route('admin.reports.show', ['report' => $target, ...request()->query()]) }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 hover:shadow">
                <p class="text-sm font-medium text-gray-500">{{ $label }}</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $key === 'maintenance_cost' ? number_format($dashboard['metrics'][$key], 2) : number_format($dashboard['metrics'][$key]) }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        @include('admin.reports.partials.chart', ['title' => 'Work orders by status', 'labels' => $dashboard['charts']['work_orders_by_status']->pluck('label'), 'datasets' => [['label' => 'Work orders', 'data' => $dashboard['charts']['work_orders_by_status']->pluck('value'), 'backgroundColor' => '#047857']]])
        @include('admin.reports.partials.chart', ['title' => 'Assets by status', 'type' => 'doughnut', 'labels' => $dashboard['charts']['assets_by_status']->pluck('label'), 'datasets' => [['label' => 'Assets', 'data' => $dashboard['charts']['assets_by_status']->pluck('value'), 'backgroundColor' => ['#047857','#0f766e','#d97706','#dc2626','#64748b','#111827']]]])
        @include('admin.reports.partials.chart', ['title' => 'Work order trend', 'type' => 'line', 'labels' => $dashboard['charts']['work_order_trend']->pluck('label'), 'datasets' => [['label' => 'Created', 'data' => $dashboard['charts']['work_order_trend']->pluck('created_count'), 'borderColor' => '#047857', 'backgroundColor' => '#047857'], ['label' => 'Completed', 'data' => $dashboard['charts']['work_order_trend']->pluck('completed_count'), 'borderColor' => '#2563eb', 'backgroundColor' => '#2563eb']]])
        @include('admin.reports.partials.chart', ['title' => 'Inventory health', 'type' => 'doughnut', 'labels' => $dashboard['charts']['inventory_health']->pluck('label'), 'datasets' => [['label' => 'Items', 'data' => $dashboard['charts']['inventory_health']->pluck('value'), 'backgroundColor' => ['#047857','#d97706','#dc2626']]]])
    </div>

    <section class="mt-6 border-t border-gray-200 pt-5 text-sm text-gray-500">
        <h2 class="font-semibold text-gray-900">Data notes</h2>
        <ul class="mt-2 space-y-1">@foreach($dashboard['limitations'] as $limitation)<li>{{ $limitation }}</li>@endforeach</ul>
    </section>
</x-app-layout>
