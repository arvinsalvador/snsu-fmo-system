<form method="GET" class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm" x-data="{ period: @js($filters['period'] ?? 'this_month') }">
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
        <div>
            <label for="period" class="block text-xs font-semibold uppercase text-gray-500">Period</label>
            <select id="period" name="period" x-model="period" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                @foreach ($periods as $period)<option value="{{ $period }}" @selected(($filters['period'] ?? '') === $period)>{{ str($period)->replace('_', ' ')->title() }}</option>@endforeach
            </select>
        </div>
        <div x-show="period === 'custom'">
            <label for="date_from" class="block text-xs font-semibold uppercase text-gray-500">From</label>
            <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="mt-1 w-full rounded-md border-gray-300 text-sm">
        </div>
        <div x-show="period === 'custom'">
            <label for="date_to" class="block text-xs font-semibold uppercase text-gray-500">To</label>
            <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="mt-1 w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <label for="building_id" class="block text-xs font-semibold uppercase text-gray-500">Building</label>
            <select id="building_id" name="building_id" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="">All buildings</option>@foreach($buildings as $building)<option value="{{ $building->id }}" @selected(($filters['building_id'] ?? null) == $building->id)>{{ $building->name }}</option>@endforeach</select>
        </div>
        <div>
            <label for="floor_id" class="block text-xs font-semibold uppercase text-gray-500">Floor</label>
            <select id="floor_id" name="floor_id" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="">All floors</option>@foreach($floors as $floor)<option value="{{ $floor->id }}" @selected(($filters['floor_id'] ?? null) == $floor->id)>{{ $floor->building?->name }} / {{ $floor->floor_name }}</option>@endforeach</select>
        </div>
        <div>
            <label for="room_id" class="block text-xs font-semibold uppercase text-gray-500">Room</label>
            <select id="room_id" name="room_id" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="">All rooms</option>@foreach($rooms as $room)<option value="{{ $room->id }}" @selected(($filters['room_id'] ?? null) == $room->id)>{{ $room->room_code }}</option>@endforeach</select>
        </div>
        @if (($report ?? null) === 'work-orders')
            <div><label for="work_order_status_id" class="block text-xs font-semibold uppercase text-gray-500">Status</label><select id="work_order_status_id" name="work_order_status_id" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="">All statuses</option>@foreach($workOrderStatuses as $status)<option value="{{ $status->id }}" @selected(($filters['work_order_status_id'] ?? null) == $status->id)>{{ $status->name }}</option>@endforeach</select></div>
            <div><label for="priority_id" class="block text-xs font-semibold uppercase text-gray-500">Priority</label><select id="priority_id" name="priority_id" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="">All priorities</option>@foreach($priorities as $priority)<option value="{{ $priority->id }}" @selected(($filters['priority_id'] ?? null) == $priority->id)>{{ $priority->name }}</option>@endforeach</select></div>
        @endif
        @if (in_array(($report ?? null), ['assets', 'maintenance-schedules', 'maintenance-records'], true))
            <div><label for="asset_category_id" class="block text-xs font-semibold uppercase text-gray-500">Asset category</label><select id="asset_category_id" name="asset_category_id" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="">All categories</option>@foreach($assetCategories as $category)<option value="{{ $category->id }}" @selected(($filters['asset_category_id'] ?? null) == $category->id)>{{ $category->name }}</option>@endforeach</select></div>
        @endif
        @if (($report ?? null) === 'maintenance-records')
            <div><label for="maintenance_review_status" class="block text-xs font-semibold uppercase text-gray-500">Review status</label><select id="maintenance_review_status" name="maintenance_review_status" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="">All reviews</option>@foreach(['pending_review','correction_requested','approved','rejected'] as $status)<option value="{{ $status }}" @selected(($filters['maintenance_review_status'] ?? null) === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>@endforeach</select></div>
        @endif
        @if (($report ?? null) === 'inventory')
            <div><label for="inventory_category_id" class="block text-xs font-semibold uppercase text-gray-500">Category</label><select id="inventory_category_id" name="inventory_category_id" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="">All categories</option>@foreach($inventoryCategories as $category)<option value="{{ $category->id }}" @selected(($filters['inventory_category_id'] ?? null) == $category->id)>{{ $category->name }}</option>@endforeach</select></div>
        @endif
        @if (($report ?? null) !== null)
            <div><label for="search" class="block text-xs font-semibold uppercase text-gray-500">Search</label><input id="search" name="search" value="{{ $filters['search'] ?? '' }}" class="mt-1 w-full rounded-md border-gray-300 text-sm" placeholder="Code, name or title"></div>
        @endif
    </div>
    <div class="mt-4 flex flex-wrap gap-2">
        <button class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Apply filters</button>
        <a href="{{ request()->url() }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Reset</a>
    </div>
</form>
