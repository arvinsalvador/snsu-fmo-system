<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">Inventory intelligence</p>
                <h1 class="text-2xl font-semibold text-gray-900">{{ $title }}</h1>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.inventory-intelligence.dashboard') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Dashboard</a>
                <a href="{{ route('admin.inventory-intelligence.export', [$report, ...request()->query()]) }}" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white">Export CSV</a>
            </div>
        </div>
    </x-slot>

    <nav class="mb-5 flex flex-wrap gap-2 text-sm">
        @foreach ([
            'low-stock' => 'Low stock',
            'out-of-stock' => 'Out of stock',
            'fast-moving' => 'Fast moving',
            'slow-moving' => 'Slow moving',
            'monthly-consumption' => 'Monthly consumption',
        ] as $key => $label)
            <a href="{{ route('admin.inventory-intelligence.report', $key) }}" class="rounded-md px-3 py-2 font-semibold {{ $report === $key ? 'bg-emerald-700 text-white' : 'border border-gray-300 text-gray-700' }}">{{ $label }}</a>
        @endforeach
    </nav>

    <form method="GET" class="mb-5 grid gap-3 rounded-lg border border-gray-200 bg-white p-4 md:grid-cols-6">
        @if($report !== 'monthly-consumption')
            <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search materials" class="rounded-md border-gray-300 text-sm md:col-span-2">
            <select name="category_id" class="rounded-md border-gray-300 text-sm">
                <option value="">All categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-md border-gray-300 text-sm">
                <option value="">All statuses</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
            </select>
        @endif
        @if(in_array($report, ['fast-moving', 'slow-moving', 'monthly-consumption'], true))
            <input name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="rounded-md border-gray-300 text-sm">
            <input name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="rounded-md border-gray-300 text-sm">
        @endif
        <div class="flex gap-2">
            <button class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Filter</button>
            <a href="{{ route('admin.inventory-intelligence.report', $report) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Reset</a>
        </div>
    </form>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                @if($report === 'monthly-consumption')
                    <tr><th class="px-4 py-3">Month</th><th class="px-4 py-3">Consumed quantity</th></tr>
                @else
                    <tr>
                        <th class="px-4 py-3">Item</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Current</th>
                        <th class="px-4 py-3">Minimum</th>
                        <th class="px-4 py-3">Consumed</th>
                        <th class="px-4 py-3">Movements</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                @endif
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($records as $record)
                    @if($report === 'monthly-consumption')
                        <tr><td class="px-4 py-4 font-medium text-gray-900">{{ $record->month }}</td><td class="px-4 py-4 text-gray-700">{{ number_format((float) $record->consumed_quantity, 2) }}</td></tr>
                    @else
                        <tr>
                            <td class="px-4 py-4"><p class="font-medium text-gray-900">{{ $record->name }}</p><p class="text-xs text-gray-500">{{ $record->item_code }} · {{ $record->unit }}</p></td>
                            <td class="px-4 py-4 text-gray-700">{{ $record->category?->name ?? $record->category_name }}</td>
                            <td class="px-4 py-4 text-gray-700">{{ $record->current_stock }}</td>
                            <td class="px-4 py-4 text-gray-700">{{ $record->minimum_stock }}</td>
                            <td class="px-4 py-4 text-gray-700">{{ isset($record->consumed_quantity) ? number_format((float) $record->consumed_quantity, 2) : '' }}</td>
                            <td class="px-4 py-4 text-gray-700">{{ $record->movement_count ?? '' }}</td>
                            <td class="px-4 py-4"><span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">{{ ucfirst($record->status) }}</span></td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-500">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    @if(method_exists($records, 'links'))
        <div class="mt-5">{{ $records->links() }}</div>
    @endif
</x-app-layout>
