@can('viewMaterials', $workOrder)
    <section class="border-t border-gray-200 pt-7">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Work order materials</h2>
                <p class="mt-1 text-sm text-gray-500">Issued quantities deduct inventory and create immutable stock movement history.</p>
            </div>
        </div>

        <div class="overflow-hidden border-y border-gray-200">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Item</th>
                        <th class="px-4 py-3">Requested</th>
                        <th class="px-4 py-3">Issued</th>
                        <th class="px-4 py-3">Used</th>
                        <th class="px-4 py-3">Issued by</th>
                        @can('manageMaterials', $workOrder)
                            <th class="px-4 py-3 text-right">Actions</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($workOrder->materials as $material)
                        <tr>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-gray-900">{{ $material->inventoryItem?->name }}</p>
                                <p class="text-xs text-gray-500">{{ $material->inventoryItem?->item_code }} · {{ $material->inventoryItem?->unit }}</p>
                                @if($material->remarks)
                                    <p class="mt-1 text-xs text-gray-500">{{ $material->remarks }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-gray-700">{{ $material->quantity_requested }}</td>
                            <td class="px-4 py-4 text-gray-700">{{ $material->quantity_issued }}</td>
                            <td class="px-4 py-4 text-gray-700">{{ $material->quantity_used }}</td>
                            <td class="px-4 py-4 text-gray-700">
                                {{ $material->issuer?->name ?? 'Not issued' }}
                                @if($material->issued_at)
                                    <span class="block text-xs text-gray-500">{{ $material->issued_at->format('M j, Y g:i A') }}</span>
                                @endif
                            </td>
                            @can('manageMaterials', $workOrder)
                                <td class="px-4 py-4">
                                    <form method="POST" action="{{ route('work-orders.materials.update', [$workOrder, $material]) }}" class="grid min-w-72 gap-2 sm:grid-cols-4">
                                        @csrf
                                        @method('PATCH')
                                        <input name="quantity_requested" type="number" min="0" step="0.01" value="{{ old('quantity_requested', $material->quantity_requested) }}" class="rounded-md border-gray-300 text-xs" aria-label="Requested quantity">
                                        <input name="quantity_issued" type="number" min="0" step="0.01" value="{{ old('quantity_issued', $material->quantity_issued) }}" class="rounded-md border-gray-300 text-xs" aria-label="Issued quantity">
                                        <input name="quantity_used" type="number" min="0" step="0.01" value="{{ old('quantity_used', $material->quantity_used) }}" class="rounded-md border-gray-300 text-xs" aria-label="Used quantity">
                                        <button class="rounded-md bg-gray-900 px-3 py-2 text-xs font-semibold text-white">Update</button>
                                    </form>
                                    @if((float) $material->quantity_issued === 0.0 && (float) $material->quantity_used === 0.0)
                                        <form method="POST" action="{{ route('work-orders.materials.destroy', [$workOrder, $material]) }}" class="mt-2 text-right">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-xs font-semibold text-red-700">Remove</button>
                                        </form>
                                    @endif
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">No materials recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @can('manageMaterials', $workOrder)
            <form method="POST" action="{{ route('work-orders.materials.store', $workOrder) }}" class="mt-6 grid gap-4 rounded-lg border border-gray-200 bg-gray-50 p-4 md:grid-cols-6">
                @csrf
                <div class="md:col-span-2">
                    <label for="inventory_item_id" class="block text-sm font-semibold text-gray-900">Material</label>
                    <select id="inventory_item_id" name="inventory_item_id" required class="mt-2 w-full rounded-md border-gray-300 text-sm">
                        <option value="">Select item</option>
                        @foreach($inventoryItems as $item)
                            <option value="{{ $item->id }}" @selected((int) old('inventory_item_id') === $item->id)>{{ $item->name }} ({{ $item->current_stock }} {{ $item->unit }})</option>
                        @endforeach
                    </select>
                    @error('inventory_item_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="quantity_requested" class="block text-sm font-semibold text-gray-900">Requested</label>
                    <input id="quantity_requested" name="quantity_requested" type="number" min="0" step="0.01" required value="{{ old('quantity_requested', 0) }}" class="mt-2 w-full rounded-md border-gray-300 text-sm">
                    @error('quantity_requested')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="quantity_issued" class="block text-sm font-semibold text-gray-900">Issue now</label>
                    <input id="quantity_issued" name="quantity_issued" type="number" min="0" step="0.01" value="{{ old('quantity_issued', 0) }}" class="mt-2 w-full rounded-md border-gray-300 text-sm">
                    @error('quantity_issued')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="quantity_used" class="block text-sm font-semibold text-gray-900">Used</label>
                    <input id="quantity_used" name="quantity_used" type="number" min="0" step="0.01" value="{{ old('quantity_used', 0) }}" class="mt-2 w-full rounded-md border-gray-300 text-sm">
                    @error('quantity_used')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-6">
                    <label for="material_remarks" class="block text-sm font-semibold text-gray-900">Remarks</label>
                    <textarea id="material_remarks" name="remarks" rows="2" maxlength="5000" class="mt-2 w-full rounded-md border-gray-300 text-sm">{{ old('remarks') }}</textarea>
                    @error('remarks')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-6 flex justify-end">
                    <button class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Add material</button>
                </div>
            </form>
        @endcan
    </section>
@endcan
