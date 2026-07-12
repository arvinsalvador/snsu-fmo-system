<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-sm font-medium text-emerald-700">Reporting & analytics</p><h1 class="text-2xl font-semibold text-gray-900">{{ $result['title'] }}</h1><p class="mt-1 text-sm text-gray-500">{{ $filters['date_from'] }} to {{ $filters['date_to'] }}</p></div>
            @can('exportReports')<a href="{{ route('admin.reports.export', ['report' => $report, ...request()->query()]) }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Export CSV</a>@endcan
        </div>
    </x-slot>

    @include('admin.reports.partials.tabs')
    @include('admin.reports.partials.filters')

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($result['summary'] as $label => $value)
            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-gray-500">{{ str($label)->replace('_', ' ')->title() }}</p><p class="mt-2 text-2xl font-semibold text-gray-900">{{ is_null($value) ? 'Not available' : (is_float($value) ? number_format($value, 2) : number_format($value)) }}</p></section>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        @foreach($result['charts'] as $name => $items)
            @php
                $isTrend = $name === 'trend';
                $datasets = $isTrend
                    ? [['label' => 'Created', 'data' => $items->pluck('created_count'), 'borderColor' => '#047857', 'backgroundColor' => '#047857'], ['label' => 'Completed', 'data' => $items->pluck('completed_count'), 'borderColor' => '#2563eb', 'backgroundColor' => '#2563eb']]
                    : [['label' => str($name)->replace('_', ' ')->title()->toString(), 'data' => $items->pluck('value'), 'backgroundColor' => ['#047857','#0f766e','#2563eb','#d97706','#dc2626','#64748b']]];
            @endphp
            @include('admin.reports.partials.chart', ['title' => str($name)->replace('_', ' ')->title(), 'type' => $isTrend ? 'line' : (str_contains($name, 'status') ? 'doughnut' : 'bar'), 'labels' => $items->pluck('label'), 'datasets' => $datasets])
        @endforeach
    </div>

    <section class="mt-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4"><h2 class="font-semibold text-gray-900">Detailed records</h2><p class="mt-1 text-sm text-gray-500">{{ number_format($result['records']->total()) }} matching records</p></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50"><tr>@foreach($result['columns'] as $column)<th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ $column }}</th>@endforeach</tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($result['records'] as $row)
                        @php($values = collect($row)->except(['id', 'url'])->values())
                        <tr class="hover:bg-gray-50">@foreach($values as $index => $value)<td class="whitespace-nowrap px-4 py-3 text-gray-700">@if($index === 0 && isset($row['url']))<a href="{{ $row['url'] }}" class="font-semibold text-emerald-700 hover:underline">{{ $value ?: 'View' }}</a>@else{{ is_numeric($value) ? number_format((float) $value, is_float($value) ? 2 : 0) : (blank($value) ? '—' : str($value)->replace('_', ' ')->title()) }}@endif</td>@endforeach</tr>
                    @empty
                        <tr><td colspan="{{ count($result['columns']) }}" class="px-4 py-12 text-center text-gray-500">No records match the selected filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($result['records']->hasPages())<div class="border-t border-gray-200 px-5 py-4">{{ $result['records']->links() }}</div>@endif
    </section>

    <section class="mt-6 border-t border-gray-200 pt-5 text-sm text-gray-500"><h2 class="font-semibold text-gray-900">Metric definitions</h2><dl class="mt-2 grid gap-2 md:grid-cols-2">@foreach($result['definitions'] as $term => $definition)<div><dt class="font-medium text-gray-700">{{ str($term)->replace('_', ' ')->title() }}</dt><dd>{{ $definition }}</dd></div>@endforeach</dl></section>
</x-app-layout>
