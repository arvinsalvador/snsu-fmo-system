<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">Inventory intelligence</p>
                <h1 class="text-2xl font-semibold text-gray-900">Inventory dashboard</h1>
            </div>
            <a href="{{ route('admin.inventory.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Inventory items</a>
        </div>
    </x-slot>

    <form method="GET" class="mb-6 grid gap-3 rounded-lg border border-gray-200 bg-white p-4 md:grid-cols-4">
        <div>
            <label for="from" class="block text-sm font-semibold text-gray-900">From</label>
            <input id="from" name="from" type="date" value="{{ $filters['from'] ?? $dashboard['filters']['from'] }}" class="mt-2 w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <label for="to" class="block text-sm font-semibold text-gray-900">To</label>
            <input id="to" name="to" type="date" value="{{ $filters['to'] ?? $dashboard['filters']['to'] }}" class="mt-2 w-full rounded-md border-gray-300 text-sm">
        </div>
        <div class="flex items-end gap-2 md:col-span-2">
            <button class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Apply</button>
            <a href="{{ route('admin.inventory-intelligence.dashboard') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Reset</a>
        </div>
    </form>

    <div class="grid gap-4 md:grid-cols-4">
        @foreach ([
            ['label' => 'Active items', 'value' => $dashboard['totals']['active_items']],
            ['label' => 'Low stock', 'value' => $dashboard['totals']['low_stock_items']],
            ['label' => 'Out of stock', 'value' => $dashboard['totals']['out_of_stock_items']],
            ['label' => 'Inventory units', 'value' => number_format($dashboard['totals']['inventory_units'], 2)],
        ] as $card)
            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">{{ $card['label'] }}</p>
                <p class="mt-3 text-2xl font-semibold text-gray-900">{{ $card['value'] }}</p>
            </section>
        @endforeach
    </div>

    <section class="mt-6 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-gray-900">Inventory health</h2>
                <p class="mt-1 text-sm text-gray-500">Status: {{ ucfirst($dashboard['health']['status']) }}</p>
            </div>
            <span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-800">{{ $dashboard['health']['healthy_percent'] }}% healthy</span>
        </div>
        <div class="mt-5 grid gap-4 md:grid-cols-3">
            <div><p class="text-sm text-gray-500">Healthy</p><p class="text-lg font-semibold text-gray-900">{{ $dashboard['health']['healthy_percent'] }}%</p></div>
            <div><p class="text-sm text-gray-500">Low stock</p><p class="text-lg font-semibold text-amber-700">{{ $dashboard['health']['low_stock_percent'] }}%</p></div>
            <div><p class="text-sm text-gray-500">Out of stock</p><p class="text-lg font-semibold text-red-700">{{ $dashboard['health']['out_of_stock_percent'] }}%</p></div>
        </div>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        @foreach ([['Fast moving materials', 'fast-moving', $dashboard['fast_moving']], ['Slow moving materials', 'slow-moving', $dashboard['slow_moving']]] as [$title, $report, $records])
            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="font-semibold text-gray-900">{{ $title }}</h2>
                    <a href="{{ route('admin.inventory-intelligence.report', $report) }}" class="text-sm font-semibold text-emerald-700">View report</a>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse($records as $record)
                        <div class="flex items-center justify-between gap-4 py-3">
                            <div>
                                <p class="font-medium text-gray-900">{{ $record->name }}</p>
                                <p class="text-xs text-gray-500">{{ $record->item_code }} · {{ $record->category_name }}</p>
                            </div>
                            <p class="text-sm font-semibold text-gray-700">{{ number_format((float) $record->consumed_quantity, 2) }} {{ $record->unit }}</p>
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-gray-500">No consumption data for this period.</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>

    <section class="mt-6 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="font-semibold text-gray-900">Monthly consumption</h2>
            <a href="{{ route('admin.inventory-intelligence.report', 'monthly-consumption') }}" class="text-sm font-semibold text-emerald-700">View report</a>
        </div>
        <div class="grid gap-3 md:grid-cols-6">
            @forelse($dashboard['monthly_consumption'] as $month)
                <div class="rounded-md border border-gray-200 p-3">
                    <p class="text-xs text-gray-500">{{ $month->month }}</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">{{ number_format((float) $month->consumed_quantity, 2) }}</p>
                </div>
            @empty
                <p class="text-sm text-gray-500">No monthly consumption data yet.</p>
            @endforelse
        </div>
    </section>
</x-app-layout>
