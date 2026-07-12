@php($chartId = 'report-chart-'.uniqid())
<section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm" data-chart-container>
    <h2 class="text-sm font-semibold text-gray-900">{{ $title }}</h2>
    <div class="mt-4 h-72"><canvas data-report-chart="{{ $chartId }}" role="img" aria-label="{{ $title }} chart"></canvas><p data-chart-empty class="hidden pt-24 text-center text-sm text-gray-500">No data is available for this period.</p></div>
    <script type="application/json" id="{{ $chartId }}">{!! json_encode([
        'type' => $type ?? 'bar',
        'data' => ['labels' => $labels, 'datasets' => $datasets],
        'options' => ['responsive' => true, 'maintainAspectRatio' => false, 'plugins' => ['legend' => ['display' => count($datasets) > 1 || ($type ?? 'bar') === 'doughnut']], 'scales' => ($type ?? 'bar') === 'doughnut' ? [] : ['y' => ['beginAtZero' => true]]],
    ]) !!}</script>
</section>
