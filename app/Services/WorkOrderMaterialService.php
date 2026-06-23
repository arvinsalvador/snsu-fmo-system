<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderMaterial;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkOrderMaterialService
{
    /**
     * @return Collection<int, WorkOrderMaterial>
     */
    public function list(WorkOrder $workOrder): Collection
    {
        return $workOrder->materials()
            ->with(['inventoryItem.category', 'issuer'])
            ->latest('created_at')
            ->get();
    }

    public function paginate(WorkOrder $workOrder, int $perPage = 15): LengthAwarePaginator
    {
        return $workOrder->materials()
            ->with(['inventoryItem.category', 'issuer'])
            ->latest('created_at')
            ->paginate(min(max($perPage, 10), 100))
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(WorkOrder $workOrder, array $data, User $actor): WorkOrderMaterial
    {
        return DB::transaction(function () use ($actor, $data, $workOrder): WorkOrderMaterial {
            $issued = (float) ($data['quantity_issued'] ?? 0);
            $used = (float) ($data['quantity_used'] ?? 0);
            if ($used > $issued) {
                throw ValidationException::withMessages(['quantity_used' => 'Used quantity cannot exceed issued quantity.']);
            }

            $material = $workOrder->materials()->create([
                'inventory_item_id' => $data['inventory_item_id'],
                'quantity_requested' => (float) ($data['quantity_requested'] ?? 0),
                'quantity_issued' => 0,
                'quantity_used' => $used,
                'remarks' => $data['remarks'] ?? null,
            ]);

            if ($issued > 0) {
                $material = $this->issue($material, $issued, $actor, $data['remarks'] ?? null);
            }

            return $material->refresh()->load(['inventoryItem.category', 'issuer']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(WorkOrderMaterial $material, array $data, User $actor): WorkOrderMaterial
    {
        return DB::transaction(function () use ($actor, $data, $material): WorkOrderMaterial {
            $material = WorkOrderMaterial::query()->lockForUpdate()->findOrFail($material->id);
            $additionalIssue = array_key_exists('quantity_issued', $data)
                ? (float) $data['quantity_issued'] - (float) $material->quantity_issued
                : 0.0;

            if ($additionalIssue < 0) {
                throw ValidationException::withMessages(['quantity_issued' => 'Issued quantity cannot be reduced after stock has been deducted.']);
            }

            if (array_key_exists('quantity_requested', $data)) {
                $material->quantity_requested = (float) $data['quantity_requested'];
            }
            if (array_key_exists('quantity_used', $data)) {
                $used = (float) $data['quantity_used'];
                if ($used > ((float) $material->quantity_issued + $additionalIssue)) {
                    throw ValidationException::withMessages(['quantity_used' => 'Used quantity cannot exceed issued quantity.']);
                }
                $material->quantity_used = $used;
            }
            if (array_key_exists('remarks', $data)) {
                $material->remarks = $data['remarks'];
            }
            $material->save();

            if ($additionalIssue > 0) {
                $material = $this->issue($material, $additionalIssue, $actor, $data['remarks'] ?? null);
            }

            return $material->refresh()->load(['inventoryItem.category', 'issuer']);
        });
    }

    public function delete(WorkOrderMaterial $material): void
    {
        DB::transaction(function () use ($material): void {
            $material = WorkOrderMaterial::query()->lockForUpdate()->findOrFail($material->id);
            if ((float) $material->quantity_issued > 0 || (float) $material->quantity_used > 0) {
                throw ValidationException::withMessages(['material' => 'Issued or used materials cannot be deleted.']);
            }
            $material->delete();
        });
    }

    private function issue(WorkOrderMaterial $material, float $quantity, User $actor, ?string $remarks): WorkOrderMaterial
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity_issued' => 'Issued quantity must be greater than zero.']);
        }

        $item = InventoryItem::query()->whereKey($material->inventory_item_id)->lockForUpdate()->firstOrFail();
        $before = (float) $item->current_stock;
        $after = $before - $quantity;
        if ($after < 0) {
            throw ValidationException::withMessages(['quantity_issued' => 'Inventory stock cannot become negative.']);
        }

        $item->forceFill(['current_stock' => $after])->save();
        $material->forceFill([
            'quantity_issued' => (float) $material->quantity_issued + $quantity,
            'issued_by' => $actor->id,
            'issued_at' => now(),
        ])->save();

        StockMovement::query()->create([
            'inventory_item_id' => $item->id,
            'created_by' => $actor->id,
            'movement_type' => 'work_order_usage',
            'quantity' => -1 * $quantity,
            'stock_before' => $before,
            'stock_after' => $after,
            'remarks' => trim($material->workOrder?->work_order_number.' material issuance'.($remarks ? ": {$remarks}" : '')),
            'occurred_at' => now(),
        ]);

        return $material;
    }
}
